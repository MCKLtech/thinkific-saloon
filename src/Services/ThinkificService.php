<?php

namespace WooNinja\ThinkificSaloon\Services;

use WooNinja\ThinkificSaloon\Auth\ThinkificAuthenticator;
use WooNinja\ThinkificSaloon\Connectors\ThinkificConnector;
use WooNinja\ThinkificSaloon\Interfaces\Thinkific;
use WooNinja\ThinkificSaloon\Requests\CustomProfileFieldDefinitions\CustomProfileFieldDefinition;
use WooNinja\ThinkificSaloon\Traits\MapperTrait;

final class ThinkificService implements Thinkific
{
    use MapperTrait;

    private string $api_key;
    private string $subdomain;
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


    public function __construct(string $api_key, string $subdomain, bool $is_oauth = false)
    {
        $this->api_key = $api_key;
        $this->subdomain = $subdomain;
        $this->is_oauth = $is_oauth;

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
    }

    /**
     * @return ThinkificConnector
     */
    public function connector(): ThinkificConnector
    {
        return (new ThinkificConnector($this->subdomain))
            ->authenticate(
                new ThinkificAuthenticator(
                    $this->api_key,
                    $this->subdomain,
                    $this->is_oauth
                )
            );

    }

}