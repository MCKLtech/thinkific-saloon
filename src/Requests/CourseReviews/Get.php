<?php

namespace WooNinja\ThinkificSaloon\Requests\CourseReviews;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews\Review;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $review_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "course_reviews/{$this->review_id}";
    }

    public function createDtoFromResponse(Response $response): Review
    {
        $review = $response->json();

        return new Review(
            id: $review['id'],
            rating: $review['rating'],
            title: $review['title'],
            review_text: $review['review_text'],
            user_id: $review['user_id'],
            course_id: $review['course_id'],
            approved: $review['approved'],
            created_at: Carbon::parse($review['created_at']),
        );
    }
}