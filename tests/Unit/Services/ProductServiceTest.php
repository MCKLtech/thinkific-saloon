<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\DataTransferObjects\Products\Product;
use WooNinja\ThinkificSaloon\Requests\Bundles\Courses as BundleCourses;
use WooNinja\ThinkificSaloon\Requests\Courses\Get as CourseGet;
use WooNinja\ThinkificSaloon\Requests\Products\Get;
use WooNinja\ThinkificSaloon\Requests\Products\Products;
use WooNinja\ThinkificSaloon\Requests\Products\RelatedProducts;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class ProductServiceTest extends TestCase
{
    public function test_can_get_product_by_id(): void
    {
        $productData = $this->mockProductData(['id' => 5, 'name' => 'My Product']);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($productData, 200),
        ]);

        $product = $this->service->products->get(5);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals(5, $product->id);
        $this->assertEquals('My Product', $product->name);
    }

    public function test_can_list_products(): void
    {
        $products = [
            $this->mockProductData(['id' => 1]),
            $this->mockProductData(['id' => 2]),
        ];

        $this->mockGlobalRequests([
            Products::class => MockResponse::make([
                'items' => $products,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->products->products()->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Product::class, $result[0]);
    }

    public function test_can_list_related_products(): void
    {
        $products = [$this->mockProductData(['id' => 2])];

        $this->mockGlobalRequests([
            RelatedProducts::class => MockResponse::make([
                'items' => $products,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 1, 'entries_info' => '1-1 of 1',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->products->related(1)->items());

        $this->assertCount(1, $result);
    }

    public function test_courses_returns_the_course_for_a_course_type_product(): void
    {
        $productData = $this->mockProductData(['id' => 5, 'productable_id' => 100, 'productable_type' => 'Course']);
        $courseData = $this->mockCourseData(['id' => 100]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($productData, 200),
            CourseGet::class => MockResponse::make($courseData, 200),
        ]);

        $courses = $this->service->products->courses(5);

        $this->assertCount(1, $courses);
        $this->assertInstanceOf(Course::class, $courses[0]);
        $this->assertEquals(100, $courses[0]->id);
    }

    public function test_courses_returns_bundle_courses_for_a_bundle_type_product(): void
    {
        $productData = $this->mockProductData(['id' => 6, 'productable_id' => 200, 'productable_type' => 'Bundle']);
        $bundleCourses = [
            $this->mockCourseData(['id' => 1]),
            $this->mockCourseData(['id' => 2]),
        ];

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($productData, 200),
            BundleCourses::class => MockResponse::make([
                'items' => $bundleCourses,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $courses = $this->service->products->courses(6);

        $this->assertCount(2, $courses);
    }
}
