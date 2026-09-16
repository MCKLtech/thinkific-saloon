<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Connectors;

use Saloon\Exceptions\Request\RequestException;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;
use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\GraphQL\Exceptions\GraphQLException;
use WooNinja\ThinkificSaloon\GraphQL\Exceptions\GraphQLRateLimitedException;
use WooNinja\ThinkificSaloon\GraphQL\Exceptions\MaxQueryCostExceededException;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\QuizSubmissions;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Surveys\UserSurveys;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Me;
use WooNinja\ThinkificSaloon\GraphQL\Responses\ThinkificGraphQLResponse;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

/**
 * Thinkific's GraphQL API reports every system/validation error as HTTP 200
 * with a top-level `errors` array. Saloon's AlwaysThrowOnErrors only looks at
 * the HTTP status, so without connector-level detection these responses used
 * to hydrate as "no data" and a paginated run reported success with zero rows.
 *
 * Bodies below are copied from
 * https://support.thinkific.dev/hc/en-us/articles/22166388092823-GraphQL-Error-Handling
 */
class GraphQLErrorsTest extends GraphQLTestCase
{
    private function gqlMaxCostExceeded(int $cost = 1200, int $maxCost = 1000): MockResponse
    {
        return MockResponse::make(
            body: json_encode([
                'errors' => [[
                    'message'    => "Query cost of {$cost} exceeds the maximum single query cost limit of {$maxCost}.",
                    'extensions' => ['code' => 'MAX_QUERY_COST_EXCEEDED', 'maxCost' => $maxCost],
                ]],
                'extensions' => [
                    'rateLimit' => [
                        'cost'      => $cost,
                        'remaining' => 2000,
                        'resetAt'   => '2026-06-02T00:02:00.000Z',
                        'limit'     => 2000,
                    ],
                ],
            ]),
            status: 200,
            headers: ['Content-Type' => 'application/json']
        );
    }

    private function gqlServerErrorWithPartialData(): MockResponse
    {
        return MockResponse::make(
            body: json_encode([
                'data'   => ['site' => ['quizSubmissions' => null]],
                'errors' => [[
                    'message'    => 'Sorry, something went wrong. Please contact support@thinkific.com and include this request ID 32858202-4212-49a4-9eb5-bfa11ea41a7b in your email.',
                    'locations'  => [['line' => 17, 'column' => 5]],
                    'path'       => ['site', 'quizSubmissions'],
                    'extensions' => ['code' => 'SERVER_ERROR', 'request_id' => '32858202-4212-49a4-9eb5-bfa11ea41a7b'],
                ]],
            ]),
            status: 200,
            headers: ['Content-Type' => 'application/json']
        );
    }

    // -------------------------------------------------------------------------
    // MAX_QUERY_COST_EXCEEDED — the production failure
    // -------------------------------------------------------------------------

    public function test_max_query_cost_exceeded_throws_instead_of_yielding_an_empty_page(): void
    {
        $this->mockGql([QuizSubmissions::class => $this->gqlMaxCostExceeded()]);

        try {
            iterator_to_array($this->gql->quizzes->submissions(answers_per_page: 50)->items());
            $this->fail('Expected MaxQueryCostExceededException, got an empty page');
        } catch (MaxQueryCostExceededException $e) {
            $this->assertSame(1200, $e->getCost());
            $this->assertSame(1000, $e->getMaxCost());
            $this->assertSame(['MAX_QUERY_COST_EXCEEDED'], $e->getErrorCodes());
            $this->assertTrue($e->hasErrorCode('MAX_QUERY_COST_EXCEEDED'));
            $this->assertStringContainsString('Query cost of 1200 exceeds', $e->getMessage());
        }
    }

    public function test_max_query_cost_exceeded_on_survey_answers_throws(): void
    {
        $this->mockGql([UserSurveys::class => $this->gqlMaxCostExceeded()]);

        $this->expectException(MaxQueryCostExceededException::class);

        iterator_to_array($this->gql->surveys->surveysForUser(1, user_answers: 50)->items());
    }

    public function test_max_query_cost_exceeded_does_not_consume_local_budget(): void
    {
        // Thinkific does not charge a rejected query: the body reports remaining=limit.
        $this->mockGql([QuizSubmissions::class => $this->gqlMaxCostExceeded()]);

        try {
            $this->gql->connector()->send(new QuizSubmissions());
        } catch (MaxQueryCostExceededException) {
        }

        $prefix = $this->gql->connector()->getLimiterPrefixName();
        $stored = json_decode($this->gql->connector()->rateLimitStore()->get("{$prefix}:{$prefix}"), true);

        $this->assertSame(0, $stored['hits']);
    }

    // -------------------------------------------------------------------------
    // RATE_LIMITED — first strike must be visible, not just the third
    // -------------------------------------------------------------------------

    public function test_a_single_rate_limited_response_throws(): void
    {
        $this->mockGql([QuizSubmissions::class => $this->gqlRateLimited()]);

        try {
            iterator_to_array($this->gql->quizzes->submissions()->items());
            $this->fail('Expected GraphQLRateLimitedException, got an empty page');
        } catch (GraphQLRateLimitedException $e) {
            $this->assertSame(['RATE_LIMITED'], $e->getErrorCodes());
            $this->assertSame('2026-06-02T00:02:00.000Z', $e->getResetAt());
            $this->assertSame(0, $e->getRemaining());
        }
    }

    // -------------------------------------------------------------------------
    // Generic errors and partial data
    // -------------------------------------------------------------------------

    public function test_server_error_alongside_partial_data_throws_with_request_id(): void
    {
        $this->mockGql([QuizSubmissions::class => $this->gqlServerErrorWithPartialData()]);

        try {
            iterator_to_array($this->gql->quizzes->submissions()->items());
            $this->fail('Expected GraphQLException');
        } catch (GraphQLException $e) {
            $this->assertSame(['SERVER_ERROR'], $e->getErrorCodes());
            $this->assertStringContainsString('32858202-4212-49a4-9eb5-bfa11ea41a7b', $e->getMessage());
            $this->assertSame(['site', 'quizSubmissions'], $e->getErrors()[0]['path']);
            $this->assertSame(200, $e->getResponse()->status());
        }
    }

    public function test_graphql_exceptions_are_saloon_request_exceptions_for_existing_catch_blocks(): void
    {
        $this->mockGql([QuizSubmissions::class => $this->gqlMaxCostExceeded()]);

        $this->expectException(RequestException::class);

        $this->gql->connector()->send(new QuizSubmissions());
    }

    public function test_error_without_extensions_code_still_throws(): void
    {
        $this->mockGql([
            Me::class => MockResponse::make(
                body: json_encode(['errors' => [['message' => 'Unexpected end of document']], 'data' => null]),
                status: 200,
                headers: ['Content-Type' => 'application/json']
            ),
        ]);

        try {
            $this->gql->connector()->send(new Me());
            $this->fail('Expected GraphQLException');
        } catch (GraphQLException $e) {
            $this->assertSame([], $e->getErrorCodes());
            $this->assertSame('Unexpected end of document', $e->getMessage());
        }
    }

    public function test_a_successful_response_with_no_errors_key_does_not_throw(): void
    {
        $this->mockGql([Me::class => $this->gqlResponse(['me' => ['id' => '1', 'email' => 'a@b.c']])]);

        $response = $this->gql->connector()->send(new Me());

        $this->assertTrue($response->successful());
        $this->assertFalse($response->failed());
        $this->assertSame([], $response->graphQLErrors());
    }

    public function test_an_empty_errors_array_does_not_throw(): void
    {
        $this->mockGql([
            Me::class => MockResponse::make(
                body: json_encode(['data' => ['me' => ['id' => '1']], 'errors' => []]),
                status: 200,
                headers: ['Content-Type' => 'application/json']
            ),
        ]);

        $this->assertFalse($this->gql->connector()->send(new Me())->failed());
    }

    // -------------------------------------------------------------------------
    // Edge cases on the new surface
    // -------------------------------------------------------------------------

    public function test_seconds_until_reset_is_derived_from_reset_at_and_never_below_one(): void
    {
        $this->mockGql([Me::class => $this->gqlRateLimited()]); // resetAt is in the past relative to a live clock

        try {
            $this->gql->connector()->send(new Me());
            $this->fail('Expected GraphQLRateLimitedException');
        } catch (GraphQLRateLimitedException $e) {
            $this->assertSame(max(1, strtotime('2026-06-02T00:02:00.000Z') - time()), $e->getSecondsUntilReset());
        }
    }

    public function test_seconds_until_reset_defaults_to_sixty_without_rate_limit_block(): void
    {
        $this->mockGql([
            Me::class => MockResponse::make(
                body: json_encode(['errors' => [['message' => 'API rate limit exceeded.', 'extensions' => ['code' => 'RATE_LIMITED']]]]),
                status: 200,
                headers: ['Content-Type' => 'application/json']
            ),
        ]);

        try {
            $this->gql->connector()->send(new Me());
            $this->fail('Expected GraphQLRateLimitedException');
        } catch (GraphQLRateLimitedException $e) {
            $this->assertNull($e->getRateLimit());
            $this->assertNull($e->getResetAt());
            $this->assertNull($e->getRemaining());
            $this->assertSame(60, $e->getSecondsUntilReset());
        }
    }

    public function test_max_cost_accessors_are_null_when_thinkific_omits_them(): void
    {
        $this->mockGql([
            Me::class => MockResponse::make(
                body: json_encode(['errors' => [['message' => 'too expensive', 'extensions' => ['code' => 'MAX_QUERY_COST_EXCEEDED']]]]),
                status: 200,
                headers: ['Content-Type' => 'application/json']
            ),
        ]);

        try {
            $this->gql->connector()->send(new Me());
            $this->fail('Expected MaxQueryCostExceededException');
        } catch (MaxQueryCostExceededException $e) {
            $this->assertNull($e->getCost());
            $this->assertNull($e->getMaxCost());
        }
    }

    public function test_cost_exceeded_takes_precedence_over_rate_limited_when_both_are_reported(): void
    {
        $this->mockGql([
            Me::class => MockResponse::make(
                body: json_encode(['errors' => [
                    ['message' => 'API rate limit exceeded.', 'extensions' => ['code' => 'RATE_LIMITED']],
                    ['message' => 'Query cost of 1200 exceeds the maximum single query cost limit of 1000.', 'extensions' => ['code' => 'MAX_QUERY_COST_EXCEEDED', 'maxCost' => 1000]],
                ]]),
                status: 200,
                headers: ['Content-Type' => 'application/json']
            ),
        ]);

        try {
            $this->gql->connector()->send(new Me());
            $this->fail('Expected MaxQueryCostExceededException');
        } catch (MaxQueryCostExceededException $e) {
            $this->assertSame(['RATE_LIMITED', 'MAX_QUERY_COST_EXCEEDED'], $e->getErrorCodes());
            $this->assertSame(1000, $e->getMaxCost());
            $this->assertStringContainsString(' | ', $e->getMessage());
        }
    }

    public function test_response_class_exposes_graphql_errors(): void
    {
        $this->mockGql([Me::class => $this->gqlRateLimited()]);

        try {
            $this->gql->connector()->send(new Me());
            $this->fail('Expected GraphQLRateLimitedException');
        } catch (GraphQLRateLimitedException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf(ThinkificGraphQLResponse::class, $response);
            $this->assertTrue($response->hasGraphQLErrors());
            $this->assertSame('RATE_LIMITED', $response->graphQLErrors()[0]['extensions']['code']);
            $this->assertTrue($response->failed());
        }
    }

    public function test_non_json_200_body_is_not_treated_as_a_graphql_error(): void
    {
        // e.g. a proxy or Cloudflare interstitial answering 200 with HTML. This is
        // not a GraphQL error: extractErrors() yields []. The connector's existing
        // handleTooManyAttempts() treats an unparseable body as a rate-limit strike
        // and marks the limiter exceeded, so the plugin throws — pre-existing
        // behaviour, pinned here so the two mechanisms stay distinct.
        $this->mockGql([Me::class => MockResponse::make(body: '<html>challenge</html>', status: 200, headers: ['Content-Type' => 'text/html'])]);

        try {
            $this->gql->connector()->send(new Me());
            $this->fail('Expected RateLimitReachedException');
        } catch (RateLimitReachedException $e) {
            $this->assertNotInstanceOf(GraphQLException::class, $e);
        }
    }

    public function test_errors_key_holding_a_non_array_is_ignored(): void
    {
        $this->mockGql([
            Me::class => MockResponse::make(
                body: json_encode(['data' => ['me' => ['id' => '1']], 'errors' => null]),
                status: 200,
                headers: ['Content-Type' => 'application/json']
            ),
        ]);

        $this->assertFalse($this->gql->connector()->send(new Me())->failed());
    }

    // -------------------------------------------------------------------------
    // Interaction with the existing rate-limit plugin (BC)
    // -------------------------------------------------------------------------

    public function test_third_rate_limited_strike_still_raises_the_plugin_exception_and_blocks_further_requests(): void
    {
        $this->mockGql([Me::class => $this->gqlRateLimited()]);
        $connector = $this->gql->connector();

        for ($strike = 1; $strike <= 2; $strike++) {
            try {
                $connector->send(new Me());
                $this->fail("Strike {$strike}: expected GraphQLRateLimitedException");
            } catch (GraphQLRateLimitedException) {
                // strikes 1–2 are now visible to the caller instead of empty pages
            }
        }

        try {
            $connector->send(new Me());
            $this->fail('Strike 3: expected RateLimitReachedException');
        } catch (RateLimitReachedException $e) {
            $this->assertSame(RateLimitReachedException::class, $e::class);
        }

        // The store is now marked exceeded, so the 4th call is refused locally before sending.
        $this->expectException(RateLimitReachedException::class);
        $connector->send(new Me());
    }

    public function test_http_errors_still_fail_as_before(): void
    {
        $this->mockGql([Me::class => MockResponse::make(body: json_encode(['message' => 'Unauthorized']), status: 401)]);

        try {
            $this->gql->connector()->send(new Me());
            $this->fail('Expected RequestException');
        } catch (RequestException $e) {
            $this->assertNotInstanceOf(GraphQLException::class, $e);
            $this->assertSame(401, $e->getResponse()->status());
        }
    }
}
