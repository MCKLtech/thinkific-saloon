<?php

namespace WooNinja\ThinkificSaloon\Tests;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Saloon\Http\Response;
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
        ?int $total = null
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
    protected function assertResponseFailed(Response $response, ?int $expectedStatus = null): void
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
        // No 'slug' key: verified against Thinkific's published OpenAPI
        // schema that BundleResponse has no slug field at all.
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

    /**
     * Create a mock chapter data array (matches Thinkific API structure)
     */
    protected function mockChapterData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'A Chapter',
            'position' => 1,
            'description' => 'Chapter description',
            'duration_in_seconds' => 600,
            'content_ids' => [1, 2],
        ], $overrides);
    }

    /**
     * Create a mock content data array (matches Thinkific API structure)
     */
    protected function mockContentData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'A Lesson',
            'position' => 1,
            'chapter_id' => 1,
            'contentable_type' => 'Lesson',
            'free' => false,
            'take_url' => 'https://test-subdomain.thinkific.com/courses/take/my-course/lessons/1',
        ], $overrides);
    }

    /**
     * Create a mock coupon data array (matches Thinkific API structure)
     */
    protected function mockCouponData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'code' => 'SAVE20',
            'note' => 'Launch promo',
            'quantity_used' => 0,
            'quantity' => 100,
            'promotion_id' => 1,
            'created_at' => '2018-07-12T23:19:00.154Z',
        ], $overrides);
    }

    /**
     * Create a mock course review data array (matches Thinkific API structure)
     */
    protected function mockReviewData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'rating' => 5,
            'title' => 'Great course',
            'review_text' => 'Learned a lot.',
            'user_id' => 1,
            'course_id' => 1,
            'approved' => true,
            'created_at' => '2018-07-12T23:19:00.154Z',
        ], $overrides);
    }

    /**
     * Create a mock custom profile field definition data array (matches Thinkific API structure)
     */
    protected function mockCustomProfileFieldDefinitionData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'label' => 'Phone',
            'field_type' => 'text',
            'required' => false,
        ], $overrides);
    }

    /**
     * Create a mock group data array (matches Thinkific API structure)
     */
    protected function mockGroupData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'A Group',
            'token' => 'abc123token',
            'created_at' => '2018-07-12T23:19:00.154Z',
        ], $overrides);
    }

    /**
     * Create a mock instructor data array (matches Thinkific API structure)
     */
    protected function mockInstructorData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'user_id' => 1,
            'title' => 'Lead Instructor',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'bio' => 'Jane teaches things.',
            'slug' => 'jane-doe',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'email' => 'jane@example.com',
            'created_at' => '2018-07-12T23:19:00.154Z',
        ], $overrides);
    }

    /**
     * Create a mock order data array (matches Thinkific API structure)
     */
    protected function mockOrderData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'created_at' => '2018-07-12T23:19:00.154Z',
            'user_id' => 1,
            'user_email' => 'bob@example.com',
            'user_name' => 'Bob Smith',
            'product_name' => 'My Course',
            'product_id' => 1,
            'amount_dollars' => 99.0,
            'amount_cents' => 9900,
            'subscription' => false,
            'coupon_code' => null,
            'coupon_id' => null,
            'affiliate_referral_code' => null,
            'status' => 'complete',
            'items' => [],
        ], $overrides);
    }

    /**
     * Create a mock product data array (matches Thinkific API structure)
     */
    protected function mockProductData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'created_at' => '2018-07-12T23:19:00.154Z',
            'productable_id' => 1,
            'productable_type' => 'Course',
            'price' => 99.0,
            'position' => 1,
            'status' => 'published',
            'name' => 'My Course',
            'private' => false,
            'hidden' => false,
            'subscription' => false,
            'days_until_expiry' => null,
            'has_certificate' => false,
            'keywords' => null,
            'seo_title' => null,
            'seo_description' => null,
            'collection_ids' => [],
            'related_product_ids' => [],
            'description' => 'Product description',
            'card_image_url' => null,
            'slug' => 'my-course',
            'product_prices' => [],
        ], $overrides);
    }

    /**
     * Create a mock promotion data array (matches Thinkific API structure)
     */
    protected function mockPromotionData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'Launch Promo',
            'description' => 'A promotion',
            'starts_at' => '2018-07-12T23:19:00.154Z',
            'expires_at' => '2018-08-12T23:19:00.154Z',
            'discount_type' => 'percentage',
            'amount' => 20,
            'coupon_ids' => [1],
            'product_ids' => [1],
            'duration' => null,
        ], $overrides);
    }

    /**
     * Create a mock site script data array (matches Thinkific API structure)
     */
    protected function mockSiteScriptData(array $overrides = []): array
    {
        return array_merge([
            'id' => '1',
            'name' => 'Analytics',
            'description' => 'Tracking script',
            'page_scopes' => ['all'],
            'location' => 'footer',
            'load_method' => 'default',
            'category' => 'functional',
            'content' => "console.log('hi')",
            'src' => null,
        ], $overrides);
    }

    /**
     * Create a mock webhook data array (matches Thinkific API structure)
     * @see CLAUDE.md for the real captured response shape
     */
    protected function mockWebhookData(array $overrides = []): array
    {
        return array_merge([
            'id' => '01KC4KC2FW5JQM12345',
            'target_url' => 'https://example.net/webhook-ingest',
            'status' => 'active',
            'topic' => 'lesson.completed',
            'created_at' => '2025-12-10T17:01:35.000+00:00',
            'created_by' => '123',
            'updated_at' => '2025-12-10T17:01:35.000+00:00',
            'updated_by' => '123',
        ], $overrides);
    }
}