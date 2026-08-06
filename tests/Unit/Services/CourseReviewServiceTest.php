<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews\CreateReview;
use WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews\Review;
use WooNinja\ThinkificSaloon\Requests\CourseReviews\Create;
use WooNinja\ThinkificSaloon\Requests\CourseReviews\CourseReviews;
use WooNinja\ThinkificSaloon\Requests\CourseReviews\Get;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class CourseReviewServiceTest extends TestCase
{
    public function test_can_get_review_by_id(): void
    {
        $reviewData = $this->mockReviewData(['id' => 4, 'rating' => 4]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($reviewData, 200),
        ]);

        $review = $this->service->course_reviews->get(4);

        $this->assertInstanceOf(Review::class, $review);
        $this->assertEquals(4, $review->id);
        $this->assertEquals(4, $review->rating);
    }

    public function test_can_create_review(): void
    {
        $reviewData = $this->mockReviewData(['id' => 10, 'approved' => false]);

        $this->mockGlobalRequests([
            Create::class => MockResponse::make($reviewData, 201),
        ]);

        $review = $this->service->course_reviews->create(new CreateReview(
            course_id: 1,
            rating: 5,
            title: 'Great course',
            review_text: 'Learned a lot.',
            user_id: 1,
            approved: false,
        ));

        $this->assertEquals(10, $review->id);
        $this->assertFalse($review->approved);
    }

    public function test_can_list_reviews_for_a_course(): void
    {
        $reviews = [
            $this->mockReviewData(['id' => 1]),
            $this->mockReviewData(['id' => 2]),
        ];

        $this->mockGlobalRequests([
            CourseReviews::class => MockResponse::make([
                'items' => $reviews,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->course_reviews->reviews(1)->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Review::class, $result[0]);
    }
}
