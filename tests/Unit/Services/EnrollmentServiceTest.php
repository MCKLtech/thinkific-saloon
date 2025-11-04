<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\Enrollment;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Get;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Enrollments;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class EnrollmentServiceTest extends TestCase
{
    public function test_can_get_enrollment_by_id(): void
    {
        // Arrange
        $enrollmentData = $this->mockEnrollmentData(['id' => 500]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($enrollmentData, 200)
        ]);

        // Act
        $enrollment = $this->service->enrollments->get(500);

        // Assert
        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals(500, $enrollment->id);
        $this->assertEquals('bob@example.com', $enrollment->user_email);
        $this->assertEquals('Bob Smith', $enrollment->user_name);
    }

    public function test_can_list_enrollments(): void
    {
        // Arrange
        $enrollments = [
            $this->mockEnrollmentData(['id' => 1, 'user_name' => 'Alice Smith']),
            $this->mockEnrollmentData(['id' => 2, 'user_name' => 'Bob Jones']),
        ];

        $this->mockGlobalRequests([
            Enrollments::class => MockResponse::make([
                'items' => $enrollments,
                'meta' => [
                    'pagination' => [
                        'current_page' => 1,
                        'next_page' => null,
                        'prev_page' => 0,
                        'total_pages' => 1,
                        'total_items' => 2,
                        'entries_info' => '1-2 of 2'
                    ]
                ]
            ], 200)
        ]);

        // Act
        $paginator = $this->service->enrollments->enrollments();
        $result = iterator_to_array($paginator->items());

        // Assert
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Enrollment::class, $result[0]);
        $this->assertEquals('Alice Smith', $result[0]->user_name);
        $this->assertEquals('Bob Jones', $result[1]->user_name);
    }

    public function test_enrollment_has_completion_fields(): void
    {
        // Arrange
        $enrollmentData = $this->mockEnrollmentData([
            'id' => 600,
            'percentage_completed' => 75,
            'completed' => false,
            'started_at' => '2024-01-01T10:00:00Z',
            'activated_at' => '2024-01-01T10:00:00Z',
            'completed_at' => '2024-01-31T10:00:00Z',
            'updated_at' => '2024-01-15T14:30:00Z'
        ]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($enrollmentData, 200)
        ]);

        // Act
        $enrollment = $this->service->enrollments->get(600);

        // Assert - Check completion tracking fields
        $this->assertEquals(75, $enrollment->percentage_completed);
        $this->assertFalse($enrollment->completed);
        $this->assertInstanceOf(\Carbon\Carbon::class, $enrollment->started_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $enrollment->activated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $enrollment->completed_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $enrollment->updated_at);
    }

    public function test_enrollment_has_expiry_and_trial_fields(): void
    {
        // Arrange
        $enrollmentData = $this->mockEnrollmentData([
            'id' => 700,
            'expired' => true,
            'is_free_trial' => true,
            'expiry_date' => '2024-12-31T23:59:59Z'
        ]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($enrollmentData, 200)
        ]);

        // Act
        $enrollment = $this->service->enrollments->get(700);

        // Assert - Check expiry and trial fields
        $this->assertTrue($enrollment->expired);
        $this->assertTrue($enrollment->is_free_trial);
        $this->assertInstanceOf(\Carbon\Carbon::class, $enrollment->expiry_date);
    }

    public function test_enrollment_has_user_and_course_relationships(): void
    {
        // Arrange
        $enrollmentData = $this->mockEnrollmentData([
            'id' => 800,
            'user_id' => 123,
            'user_email' => 'test@example.com',
            'user_name' => 'Test User',
            'course_id' => 456,
            'course_name' => 'Advanced PHP Course'
        ]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($enrollmentData, 200)
        ]);

        // Act
        $enrollment = $this->service->enrollments->get(800);

        // Assert - Check user and course relationship fields
        $this->assertEquals(123, $enrollment->user_id);
        $this->assertEquals('test@example.com', $enrollment->user_email);
        $this->assertEquals('Test User', $enrollment->user_name);
        $this->assertEquals(456, $enrollment->course_id);
        $this->assertEquals('Advanced PHP Course', $enrollment->course_name);
    }

    public function test_enrollment_service_exists(): void
    {
        $this->assertInstanceOf(
            \WooNinja\ThinkificSaloon\Services\EnrollmentService::class,
            $this->service->enrollments
        );
    }

    public function test_enrollment_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service->enrollments, 'get'));
        $this->assertTrue(method_exists($this->service->enrollments, 'enrollments'));
        $this->assertTrue(method_exists($this->service->enrollments, 'create'));
        $this->assertTrue(method_exists($this->service->enrollments, 'update'));
        $this->assertTrue(method_exists($this->service->enrollments, 'expire'));
        $this->assertTrue(method_exists($this->service->enrollments, 'enrollmentsForCourse'));
        $this->assertTrue(method_exists($this->service->enrollments, 'enrollmentsForUser'));
    }
}
