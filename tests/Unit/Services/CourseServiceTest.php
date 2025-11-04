<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\Requests\Courses\Get;
use WooNinja\ThinkificSaloon\Requests\Courses\Courses;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class CourseServiceTest extends TestCase
{
    public function test_can_get_course_by_id(): void
    {
        // Arrange
        $courseData = $this->mockCourseData(['id' => 100]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($courseData, 200)
        ]);

        // Act
        $course = $this->service->courses->get(100);

        // Assert
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(100, $course->id);
        $this->assertEquals('My Course', $course->name);
        $this->assertEquals('my-course', $course->slug);
    }

    public function test_can_list_courses(): void
    {
        // Arrange
        $courses = [
            $this->mockCourseData(['id' => 1, 'name' => 'Course 1']),
            $this->mockCourseData(['id' => 2, 'name' => 'Course 2']),
        ];

        $this->mockGlobalRequests([
            Courses::class => MockResponse::make([
                'items' => $courses,
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
        $paginator = $this->service->courses->courses();
        $result = iterator_to_array($paginator->items());

        // Assert
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Course::class, $result[0]);
        $this->assertEquals('Course 1', $result[0]->name);
        $this->assertEquals('Course 2', $result[1]->name);
    }

    public function test_course_has_all_thinkific_fields(): void
    {
        // Arrange
        $courseData = $this->mockCourseData([
            'id' => 200,
            'subtitle' => 'Advanced Course',
            'keywords' => 'php,api,testing',
            'duration' => '45',
            'administrator_user_ids' => [10, 20, 30],
            'chapter_ids' => [1, 2, 3, 4]
        ]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($courseData, 200)
        ]);

        // Act
        $course = $this->service->courses->get(200);

        // Assert - Check Thinkific-specific fields
        $this->assertEquals('Advanced Course', $course->subtitle);
        $this->assertEquals('php,api,testing', $course->keywords);
        $this->assertEquals('45', $course->duration);
        $this->assertEquals([10, 20, 30], $course->administrator_user_ids);
        $this->assertCount(4, $course->chapter_ids);
    }

    public function test_course_has_media_fields(): void
    {
        // Arrange
        $courseData = $this->mockCourseData([
            'id' => 300,
            'banner_image_url' => 'http://example.com/banner.jpg',
            'course_card_image_url' => 'http://example.com/card.jpg',
            'intro_video_youtube' => 'abc123',
            'intro_video_wistia_identifier' => 'wistia456'
        ]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($courseData, 200)
        ]);

        // Act
        $course = $this->service->courses->get(300);

        // Assert - Check media fields
        $this->assertStringContainsString('banner.jpg', $course->banner_image_url);
        $this->assertStringContainsString('card.jpg', $course->course_card_image_url);
        $this->assertEquals('abc123', $course->intro_video_youtube);
        $this->assertEquals('wistia456', $course->intro_video_wistia_identifier);
    }

    public function test_course_service_exists(): void
    {
        $this->assertInstanceOf(
            \WooNinja\ThinkificSaloon\Services\CourseService::class,
            $this->service->courses
        );
    }

    public function test_course_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service->courses, 'get'));
        $this->assertTrue(method_exists($this->service->courses, 'courses'));
    }
}
