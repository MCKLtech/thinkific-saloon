<?php

namespace WooNinja\ThinkificSaloon\Tests;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Saloon\Contracts\Response;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\Connectors\ThinkificConnector;
use WooNinja\ThinkificSaloon\Services\ThinkificService;

abstract class TestCase extends BaseTestCase
{
    protected ThinkificService $service;
    protected MockClient $mockClient;
    protected string $apiKey = 'test_api_key';
    protected string $subdomain = 'test-subdomain';

    protected function setUp(): void
    {
        parent::setUp();

        // Create a fresh service instance for each test
        $this->service = new ThinkificService(
            api_key: $this->apiKey,
            subdomain: $this->subdomain,
            is_oauth: false
        );

        // Set up mock client
        $this->mockClient = new MockClient();
    }

    protected function tearDown(): void
    {
        // Clean up Global MockClient to prevent leaking into other tests
        MockClient::destroyGlobal();

        Mockery::close();
        parent::tearDown();
    }

    /**
     * Set up Global MockClient for request mocking
     * Maps request classes to MockResponse objects
     *
     * @param array $mockResponses Array mapping request classes to MockResponse objects
     * @return void
     *
     * Example:
     * $this->mockGlobalRequests([
     *     Get::class => MockResponse::make($data, 200),
     *     Users::class => MockResponse::fixture('users'),
     * ]);
     */
    protected function mockGlobalRequests(array $mockResponses): void
    {
        MockClient::global($mockResponses);
    }

    /**
     * Create a MockResponse from fixture file
     *
     * @param string $fixtureName Name of fixture file (without .json extension)
     * @param string $key Optional key within fixture (e.g., 'single_user', 'user_list')
     * @return array
     */
    protected function getFixtureData(string $fixtureName, ?string $key = null): array
    {
        $data = $this->loadFixture($fixtureName);

        return $key ? ($data[$key] ?? []) : $data;
    }

    /**
     * Create a mock response
     */
    protected function mockResponse(array $data = [], int $status = 200, array $headers = []): MockResponse
    {
        return MockResponse::make(
            body: json_encode($data),
            status: $status,
            headers: array_merge(['Content-Type' => 'application/json'], $headers)
        );
    }

    /**
     * Create a paginated mock response
     */
    protected function mockPaginatedResponse(
        array $items = [],
        int $page = 1,
        int $perPage = 25,
        int $total = null
    ): MockResponse {
        $total = $total ?? count($items);

        return $this->mockResponse([
            'items' => $items,
            'meta' => [
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]
        ]);
    }

    /**
     * Assert that a response was successful
     */
    protected function assertResponseSuccessful(Response $response): void
    {
        $this->assertTrue(
            $response->successful(),
            "Expected successful response but got status {$response->status()}"
        );
    }

    /**
     * Assert that a response failed
     */
    protected function assertResponseFailed(Response $response, int $expectedStatus = null): void
    {
        $this->assertTrue(
            $response->failed(),
            "Expected failed response but got status {$response->status()}"
        );

        if ($expectedStatus !== null) {
            $this->assertEquals(
                $expectedStatus,
                $response->status(),
                "Expected status {$expectedStatus} but got {$response->status()}"
            );
        }
    }

    /**
     * Load fixture data from JSON file
     */
    protected function loadFixture(string $name): array
    {
        $path = __DIR__ . "/Fixtures/{$name}.json";

        if (!file_exists($path)) {
            throw new \RuntimeException("Fixture not found: {$name}");
        }

        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    /**
     * Create a mock user data array (matches Thinkific API structure)
     */
    protected function mockUserData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'created_at' => '2018-07-12T23:19:00.154Z',
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'full_name' => 'Bob Smith',
            'company' => "The user's company",
            'email' => 'bob@example.com',
            'roles' => ['affiliate'],
            'avatar_url' => 'https://example.com/avatar/123',
            'bio' => "User's bio",
            'headline' => "User's headline",
            'affiliate_code' => 'abc123',
            'external_source' => 'string',
            'affiliate_commission' => 20,
            'affiliate_commission_type' => '%',
            'affiliate_payout_email' => 'bob@example.com',
            'administered_course_ids' => [[10, 20, 30]],
            'custom_profile_fields' => [
                [
                    'id' => 1,
                    'value' => '887 909 9999',
                    'label' => 'Phone',
                    'custom_profile_field_definition_id' => 1
                ]
            ]
        ], $overrides);
    }

    /**
     * Create a mock course data array (matches Thinkific API structure)
     */
    protected function mockCourseData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'My Course',
            'slug' => 'my-course',
            'subtitle' => 'My Course Subtitle',
            'product_id' => 0,
            'description' => 'Course description',
            'course_card_text' => 'my course',
            'intro_video_youtube' => 'youtube01',
            'contact_information' => 'Contact info',
            'keywords' => 'course,learn,great',
            'duration' => '22',
            'banner_image_url' => 'http://example.com/banner.jpg',
            'course_card_image_url' => 'http://example.com/card.jpg',
            'intro_video_wistia_identifier' => 'wistia0123',
            'administrator_user_ids' => [1, 2],
            'chapter_ids' => [1, 2],
            'reviews_enabled' => false,
            'user_id' => 1,
            'instructor_id' => 1
        ], $overrides);
    }

    /**
     * Create a mock enrollment data array (matches Thinkific API structure)
     */
    protected function mockEnrollmentData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'user_email' => 'bob@example.com',
            'user_name' => 'Bob Smith',
            'user_id' => 1,
            'course_name' => 'My Course',
            'course_id' => 1,
            'percentage_completed' => 1,
            'expired' => false,
            'is_free_trial' => false,
            'completed' => true,
            'started_at' => '2018-01-01T01:01:00Z',
            'activated_at' => '2018-01-01T01:01:00Z',
            'completed_at' => '2018-01-31T01:01:00Z',
            'updated_at' => '2018-01-31T01:01:00Z',
            'expiry_date' => '2019-01-01T01:01:00Z'
        ], $overrides);
    }

    /**
     * Create a mock bundle data array (matches Thinkific API structure)
     */
    protected function mockBundleData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'A Bundle',
            'description' => 'The Bundle description',
            'tagline' => 'Bundle tagline',
            'banner_image_url' => 'http://example.com/image.jpg',
            'course_ids' => [0],
            'bundle_card_image_url' => 'http://example.com/image.jpg'
        ], $overrides);
    }
}