<?php

namespace WooNinja\ThinkificSaloon\Requests\CourseReviews;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews\Review;

final class CourseReviews extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int  $course_id,
        private readonly bool $approved = false
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "course_reviews";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $review) {
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
        }, $response->json('items'));
    }

    protected function defaultQuery(): array
    {
        $params = [
            'course_id' => $this->course_id,
        ];

        if ($this->approved) {
            $params['approved'] = true;
        }

        return $params;
    }
}