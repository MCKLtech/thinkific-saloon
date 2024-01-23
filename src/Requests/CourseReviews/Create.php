<?php

namespace WooNinja\ThinkificSaloon\Requests\CourseReviews;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews\CreateReview;
use WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews\Review;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Create extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateReview $createReview
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "course_reviews";
    }

    protected function defaultBody(): array
    {
        return [
            'rating' => $this->createReview->rating,
            'title' => $this->createReview->title,
            'review_text' => $this->createReview->review_text,
            'user_id' => $this->createReview->user_id,
            'approved' => $this->createReview->approved,
        ];
    }

    protected function defaultQuery(): array
    {
        return [
            'course_id' => $this->createReview->course_id
        ];
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