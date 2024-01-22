<?php

namespace WooNinja\ThinkificSaloon\Services;

use WooNinja\ThinkificSaloon\Auth\ThinkificAuthenticator;
use WooNinja\ThinkificSaloon\Connectors\ThinkificConnector;
use WooNinja\ThinkificSaloon\Interfaces\Thinkific;

final class ThinkificService implements Thinkific
{
    private string $api_key;
    private string $subdomain;
    private bool $is_oauth;

    public BundleService $bundles;
    public CourseService $courses;
    public ChapterService $chapters;
    public ContentService $contents;

    public CouponService $coupons;

    public PromotionService $promotions;

    public EnrollmentService $enrollments;
    public ProductService $products;
    public OrderService $orders;

    public UserService $users;

    public GroupService $groups;

    public function __construct(string $api_key, string $subdomain, bool $is_oauth = false)
    {
        $this->api_key = $api_key;
        $this->subdomain = $subdomain;
        $this->is_oauth = $is_oauth;

        $this->bundles = new BundleService($this);
        $this->courses = new CourseService($this);
        $this->chapters = new ChapterService($this);
        $this->contents = new ContentService($this);
        $this->coupons = new CouponService($this);
        $this->promotions = new PromotionService($this);
        $this->enrollments = new EnrollmentService($this);
        $this->products = new ProductService($this);
        $this->orders = new OrderService($this);
        $this->users = new UserService($this);
        $this->groups = new GroupService($this);
    }

    public function connector(): ThinkificConnector
    {
        return (new ThinkificConnector($this->subdomain))
            ->authenticate(new ThinkificAuthenticator($this->api_key, $this->subdomain, $this->is_oauth));

    }

}