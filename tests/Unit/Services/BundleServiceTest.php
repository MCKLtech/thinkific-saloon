<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\Bundle;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\CreateBundleEnrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\UpdateBundleEnrollment;
use WooNinja\ThinkificSaloon\Requests\Bundles\Courses;
use WooNinja\ThinkificSaloon\Requests\Bundles\CreateEnrollment;
use WooNinja\ThinkificSaloon\Requests\Bundles\Enrollments;
use WooNinja\ThinkificSaloon\Requests\Bundles\Get;
use WooNinja\ThinkificSaloon\Requests\Bundles\UpdateEnrollment;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class BundleServiceTest extends TestCase
{
    public function test_can_get_bundle_by_id(): void
    {
        $bundleData = $this->mockBundleData(['id' => 10, 'name' => 'A Great Bundle']);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($bundleData, 200),
        ]);

        $bundle = $this->service->bundles->get(10);

        $this->assertInstanceOf(Bundle::class, $bundle);
        $this->assertEquals(10, $bundle->id);
        $this->assertEquals('A Great Bundle', $bundle->name);
    }

    public function test_can_list_bundle_courses(): void
    {
        $courses = [
            $this->mockCourseData(['id' => 1]),
            $this->mockCourseData(['id' => 2]),
        ];

        $this->mockGlobalRequests([
            Courses::class => MockResponse::make([
                'items' => $courses,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->bundles->courses(10)->items());

        $this->assertCount(2, $result);
    }

    public function test_can_list_bundle_enrollments(): void
    {
        $enrollments = [$this->mockEnrollmentData(['id' => 1])];

        $this->mockGlobalRequests([
            Enrollments::class => MockResponse::make([
                'items' => $enrollments,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 1, 'entries_info' => '1-1 of 1',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->bundles->enrollments(10)->items());

        $this->assertCount(1, $result);
    }

    public function test_can_create_bundle_enrollment(): void
    {
        $this->mockGlobalRequests([
            CreateEnrollment::class => MockResponse::make([], 201),
        ]);

        $response = $this->service->bundles->createEnrollment(new CreateBundleEnrollment(
            productable_id: 10,
            user_id: 1,
            activated_at: null,
            expiry_date: null,
        ));

        $this->assertTrue($response->successful());
    }

    public function test_can_update_bundle_enrollment(): void
    {
        $this->mockGlobalRequests([
            UpdateEnrollment::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->bundles->updateEnrollment(new UpdateBundleEnrollment(
            productable_id: 10,
            user_id: 1,
            activated_at: null,
            expiry_date: null,
        ));

        $this->assertTrue($response->successful());
    }

    public function test_expire_enrollment_sends_an_update_with_a_past_expiry_date(): void
    {
        $this->mockGlobalRequests([
            UpdateEnrollment::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->bundles->expireEnrollment(10, 1);

        $this->assertTrue($response->successful());
    }

    public function test_is_user_enrolled_returns_true_when_enrollments_exist(): void
    {
        $this->mockGlobalRequests([
            Enrollments::class => MockResponse::make([
                'items' => [$this->mockEnrollmentData(['id' => 1, 'user_id' => 1])],
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 1, 'entries_info' => '1-1 of 1',
                ]],
            ], 200),
        ]);

        $this->assertTrue($this->service->bundles->isUserEnrolled(10, 1));
    }

    public function test_is_user_enrolled_returns_false_when_no_enrollments_exist(): void
    {
        $this->mockGlobalRequests([
            Enrollments::class => MockResponse::make([
                'items' => [],
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 0, 'entries_info' => '0 of 0',
                ]],
            ], 200),
        ]);

        $this->assertFalse($this->service->bundles->isUserEnrolled(10, 'nobody@example.com'));
    }
}
