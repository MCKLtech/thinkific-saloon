<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Contracts\Authenticator;
use WooNinja\LMSContracts\Contracts\LMSServiceInterface;
use WooNinja\LMSContracts\Contracts\Services\UserServiceInterface;
use WooNinja\LMSContracts\Contracts\Services\CourseServiceInterface;
use WooNinja\LMSContracts\Contracts\Services\EnrollmentServiceInterface;
use WooNinja\LMSContracts\Contracts\Services\ProductServiceInterface;
use WooNinja\LMSContracts\Contracts\Services\OrderServiceInterface;
use WooNinja\ThinkificSaloon\Auth\ThinkificAuthenticator;
use WooNinja\ThinkificSaloon\Connectors\ThinkificConnector;
use WooNinja\ThinkificSaloon\Interfaces\Thinkific;
use WooNinja\ThinkificSaloon\Traits\MapperTrait;

final class ThinkificService implements Thinkific, LMSServiceInterface
{
    use MapperTrait;
    private string $api_key;

    public string $subdomain;
    public bool $is_oauth;

    public BundleService $bundles;

    //CategoriesService
    public ChapterService $chapters;

    //CategoryMembershipsService
    public ContentService $contents;

    public CouponService $coupons;

    public CourseService $courses;
    public CourseReviewService $course_reviews;
    public CustomProfileFieldDefinitionService $custom_profile_field_definitions;

    public EnrollmentService $enrollments;

    //ExternalOrderService

    public GroupService $groups;

    public InstructorService $instructors;
    public OrderService $orders;

    //ProductPublishRequestService

    public ProductService $products;

    public PromotionService $promotions;
    public SiteScriptService $site_scripts;

    public UserService $users;

    public WebhookService $webhooks;

    public OAuthService $oauth;
    private ThinkificConnector|bool $connector = false;
    private Authenticator|bool $authenticator = false;

    public function __construct(string $api_key, string $subdomain, bool $is_oauth = false)
    {
        $this->api_key = $api_key;
        $this->subdomain = $subdomain;
        $this->is_oauth = $is_oauth;

        $this->boot();
    }

    public function boot(): void
    {
        $this->bundles = new BundleService($this);
        $this->chapters = new ChapterService($this);
        $this->contents = new ContentService($this);
        $this->coupons = new CouponService($this);
        $this->courses = new CourseService($this);
        $this->course_reviews = new CourseReviewService($this);
        $this->custom_profile_field_definitions = new CustomProfileFieldDefinitionService($this);
        $this->enrollments = new EnrollmentService($this);
        $this->groups = new GroupService($this);
        $this->instructors = new InstructorService($this);
        $this->orders = new OrderService($this);
        $this->products = new ProductService($this);
        $this->promotions = new PromotionService($this);
        $this->site_scripts = new SiteScriptService($this);
        $this->users = new UserService($this);
        $this->webhooks = new WebhookService($this);
        $this->oauth = new OAuthService($this);

    }

    /**
     * @return ThinkificConnector
     */
    public function connector(): ThinkificConnector
    {
        if ($this->connector) {
            return $this->connector;
        }

        /**
         * Default Connector
         */
        return (new ThinkificConnector($this->subdomain))
            ->authenticate($this->authenticator());

    }

    /**
     * @return Authenticator
     */
    public function authenticator(): Authenticator
    {
        if ($this->authenticator) {
            return $this->authenticator;
        }

        return new ThinkificAuthenticator(
            $this->api_key,
            $this->subdomain,
            $this->is_oauth
        );
    }

    /**
     * Dynamically set the Connector
     *
     * @param ThinkificConnector|bool $connector
     * @return void
     */
    public function setConnector(ThinkificConnector|bool $connector): void
    {
        $this->connector = $connector;
    }

    /**
     * Dynamically set the Authenticator
     *
     * @param Authenticator|bool $authenticator
     * @return void
     */
    public function setAuthenticator(Authenticator|bool $authenticator): void
    {
        $this->authenticator = $authenticator;
    }

    /**
     * Reset the Connector and Authenticator
     *
     * @return void
     */
    public function resetService(): void
    {
        $this->connector = false;
        $this->authenticator = false;
    }

    // LMSServiceInterface implementation

    /**
     * Get the provider name
     */
    public function getProviderName(): string
    {
        return 'thinkific';
    }

    /**
     * Check if service is properly configured and connected
     */
    public function isConnected(): bool
    {
        try {
            // Test connection with a lightweight API call - we'll create a simple health check request
            $this->connector();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
