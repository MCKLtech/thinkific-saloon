<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use WooNinja\ThinkificSaloon\GraphQL\Auth\ThinkificAuthenticator;
use WooNinja\ThinkificSaloon\GraphQL\Connectors\ThinkificConnector;
use WooNinja\ThinkificSaloon\GraphQL\Services\AssignmentService;
use WooNinja\ThinkificSaloon\GraphQL\Services\CertificateService;
use WooNinja\ThinkificSaloon\GraphQL\Services\CourseService;
use WooNinja\ThinkificSaloon\GraphQL\Services\GroupService;
use WooNinja\ThinkificSaloon\GraphQL\Services\ProductService;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Me;
use WooNinja\ThinkificSaloon\GraphQL\Services\QuizService;
use WooNinja\ThinkificSaloon\GraphQL\Services\SurveyService;
use WooNinja\ThinkificSaloon\GraphQL\Services\ThinkificGraphQLService;
use WooNinja\ThinkificSaloon\GraphQL\Services\UserService;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

class ThinkificGraphQLServiceTest extends GraphQLTestCase
{
    public function test_service_initializes_correctly(): void
    {
        $service = new ThinkificGraphQLService('test-oauth-token');

        $this->assertInstanceOf(ThinkificGraphQLService::class, $service);
        $this->assertEquals('thinkific_graphql', $service->getProviderName());
    }

    public function test_service_accepts_an_optional_subdomain(): void
    {
        $service = new ThinkificGraphQLService('test-oauth-token', 'acme');

        $connector = $service->connector();

        $this->assertInstanceOf(ThinkificConnector::class, $connector);
    }

    public function test_service_boots_all_sub_services(): void
    {
        $this->assertInstanceOf(UserService::class, $this->gql->users);
        $this->assertInstanceOf(GroupService::class, $this->gql->groups);
        $this->assertInstanceOf(CourseService::class, $this->gql->courses);
        $this->assertInstanceOf(AssignmentService::class, $this->gql->assignments);
        $this->assertInstanceOf(SurveyService::class, $this->gql->surveys);
        $this->assertInstanceOf(CertificateService::class, $this->gql->certificates);
        $this->assertInstanceOf(ProductService::class, $this->gql->products);
        $this->assertInstanceOf(QuizService::class, $this->gql->quizzes);
    }

    public function test_can_get_connector(): void
    {
        $connector = $this->gql->connector();

        $this->assertInstanceOf(ThinkificConnector::class, $connector);
    }

    public function test_can_get_authenticator(): void
    {
        $authenticator = $this->gql->authenticator();

        $this->assertInstanceOf(ThinkificAuthenticator::class, $authenticator);
    }

    public function test_can_set_custom_connector(): void
    {
        $customConnector = new ThinkificConnector('custom-subdomain');
        $this->gql->setConnector($customConnector);

        $this->assertSame($customConnector, $this->gql->connector());
    }

    public function test_can_set_custom_authenticator(): void
    {
        $customAuth = new ThinkificAuthenticator('custom-token');
        $this->gql->setAuthenticator($customAuth);

        $this->assertSame($customAuth, $this->gql->authenticator());
    }

    public function test_is_connected_returns_true_when_me_query_succeeds(): void
    {
        $this->mockGql([
            Me::class => $this->gqlResponse(['me' => ['id' => '1']]),
        ]);

        $this->assertTrue($this->gql->isConnected());
    }

    public function test_is_connected_returns_false_when_query_errors(): void
    {
        $this->mockGql([
            Me::class => $this->gqlRateLimited(),
        ]);

        $this->assertFalse($this->gql->isConnected());
    }

    public function test_is_connected_returns_false_when_me_is_null(): void
    {
        $this->mockGql([
            Me::class => $this->gqlResponse(['me' => null]),
        ]);

        $this->assertFalse($this->gql->isConnected());
    }
}
