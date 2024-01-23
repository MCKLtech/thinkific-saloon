<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews\CreateReview;
use WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews\Review;
use WooNinja\ThinkificSaloon\Requests\CourseReviews\CourseReviews;
use WooNinja\ThinkificSaloon\Requests\CourseReviews\Create;
use WooNinja\ThinkificSaloon\Requests\CourseReviews\Get;

class CourseReviewService extends Resource
{

    /**
     * Get a course review by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Course%20Reviews/getCourseReviewByID
     *
     * @param int $review_id
     * @return Review
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $review_id): Review
    {
        return $this->connector
            ->send(new Get($review_id))
            ->dtoOrFail();
    }

    /**
     * Create a course review
     * @see https://developers.thinkific.com/api/api-documentation/#/Course%20Reviews/createCourseReview
     *
     * @param CreateReview $createReview
     * @return Review
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateReview $createReview): Review
    {
        return $this->connector
            ->send(new Create($createReview))
            ->dtoOrFail();
    }

    /**
     * Get reviews for a given Course
     * @see https://developers.thinkific.com/api/api-documentation/#/Course%20Reviews/getCourseReviews
     *
     * @param int $course_id
     * @param bool $approved
     * @return PagedPaginator
     */
    public function reviews(int $course_id, bool $approved = false): PagedPaginator
    {
        return $this->connector
            ->paginate(new CourseReviews($course_id, $approved));
    }

}