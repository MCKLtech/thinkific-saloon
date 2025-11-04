<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use WooNinja\ThinkificSaloon\Tests\TestCase;
use WooNinja\ThinkificSaloon\Services\ThinkificService;
use WooNinja\ThinkificSaloon\Services\UserService;
use WooNinja\ThinkificSaloon\Services\CourseService;
use WooNinja\ThinkificSaloon\Services\EnrollmentService;
use WooNinja\ThinkificSaloon\Services\ProductService;
use WooNinja\ThinkificSaloon\Services\OrderService;
use WooNinja\ThinkificSaloon\Services\BundleService;
use WooNinja\ThinkificSaloon\Services\CouponService;
use WooNinja\ThinkificSaloon\Services\PromotionService;
use WooNinja\ThinkificSaloon\Services\ChapterService;
use WooNinja\ThinkificSaloon\Services\ContentService;
use WooNinja\ThinkificSaloon\Services\CourseReviewService;
use WooNinja\ThinkificSaloon\Services\CustomProfileFieldDefinitionService;
use WooNinja\ThinkificSaloon\Services\GroupService;
use WooNinja\ThinkificSaloon\Services\InstructorService;
use WooNinja\ThinkificSaloon\Services\SiteScriptService;
use WooNinja\ThinkificSaloon\Services\WebhookService;
use WooNinja\ThinkificSaloon\Services\OAuthService;

class ThinkificServiceTest extends TestCase
{
    public function test_service_initializes_correctly(): void
    {
        $service = new ThinkificService('test-key', 'test-subdomain', false);

        $this->assertInstanceOf(ThinkificService::class, $service);
        $this->assertEquals('test-subdomain', $service->subdomain);
        $this->assertFalse($service->is_oauth);
    }

    public function test_service_boots_all_sub_services(): void
    {
        $this->assertInstanceOf(UserService::class, $this->service->users);
        $this->assertInstanceOf(CourseService::class, $this->service->courses);
        $this->assertInstanceOf(EnrollmentService::class, $this->service->enrollments);
        $this->assertInstanceOf(ProductService::class, $this->service->products);
        $this->assertInstanceOf(OrderService::class, $this->service->orders);
        $this->assertInstanceOf(BundleService::class, $this->service->bundles);
        $this->assertInstanceOf(CouponService::class, $this->service->coupons);
        $this->assertInstanceOf(PromotionService::class, $this->service->promotions);
        $this->assertInstanceOf(ChapterService::class, $this->service->chapters);
        $this->assertInstanceOf(ContentService::class, $this->service->contents);
        $this->assertInstanceOf(CourseReviewService::class, $this->service->course_reviews);
        $this->assertInstanceOf(CustomProfileFieldDefinitionService::class, $this->service->custom_profile_field_definitions);
        $this->assertInstanceOf(GroupService::class, $this->service->groups);
        $this->assertInstanceOf(InstructorService::class, $this->service->instructors);
        $this->assertInstanceOf(SiteScriptService::class, $this->service->site_scripts);
        $this->assertInstanceOf(WebhookService::class, $this->service->webhooks);
        $this->assertInstanceOf(OAuthService::class, $this->service->oauth);
    }

    public function test_can_get_connector(): void
    {
        $connector = $this->service->connector();

        $this->assertInstanceOf(\WooNinja\ThinkificSaloon\Connectors\ThinkificConnector::class, $connector);
    }

    public function test_can_get_authenticator(): void
    {
        $authenticator = $this->service->authenticator();

        $this->assertInstanceOf(\WooNinja\ThinkificSaloon\Auth\ThinkificAuthenticator::class, $authenticator);
    }

    public function test_can_set_custom_connector(): void
    {
        $customConnector = new \WooNinja\ThinkificSaloon\Connectors\ThinkificConnector('custom-subdomain');
        $this->service->setConnector($customConnector);

        $connector = $this->service->connector();

        $this->assertSame($customConnector, $connector);
    }

    public function test_can_set_custom_authenticator(): void
    {
        $customAuth = new \WooNinja\ThinkificSaloon\Auth\ThinkificAuthenticator('custom-key', 'custom-subdomain', false);
        $this->service->setAuthenticator($customAuth);

        $authenticator = $this->service->authenticator();

        $this->assertSame($customAuth, $authenticator);
    }

    public function test_can_reset_service(): void
    {
        $customConnector = new \WooNinja\ThinkificSaloon\Connectors\ThinkificConnector('custom-subdomain');
        $this->service->setConnector($customConnector);

        $this->service->resetService();

        // After reset, it should create a new connector, not return the custom one
        $connector = $this->service->connector();
        $this->assertNotSame($customConnector, $connector);
    }

    public function test_oauth_service_can_be_used(): void
    {
        $oauthService = new ThinkificService('oauth-token', 'test-subdomain', true);

        $this->assertTrue($oauthService->is_oauth);
    }
}
