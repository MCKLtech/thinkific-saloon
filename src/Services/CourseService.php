<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\LMSContracts\Contracts\Services\CourseServiceInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Courses\CourseInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Products\ProductInterface;
use WooNinja\ThinkificSaloon\Requests\Courses\Chapters;
use WooNinja\ThinkificSaloon\Requests\Courses\Courses;
use WooNinja\ThinkificSaloon\Requests\Courses\Get;

class CourseService extends Resource implements CourseServiceInterface
{
    /**
     * Get a Course by its ID.
     * @see @see https://developers.thinkific.com/api/api-documentation/#/Courses/getCourseByID
     * @param int $course_id
     * @return CourseInterface
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $course_id): CourseInterface
    {
        return $this->connector
            ->send(new Get($course_id))
            ->dtoOrFail();
    }

    /**
     * Return the associated Product of a Course.
     * Important: This expects the Course ID, not the Product / Productable ID.
     *
     * @param int $course_id
     * @return ProductInterface
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function product(int $course_id): ProductInterface
    {
        $course = $this->get($course_id);

        return $this->connector
            ->send(new \WooNinja\ThinkificSaloon\Requests\Products\Get($course->product_id))
            ->dtoOrFail();
    }

    /**
     * Get a list of Courses
     * @see https://developers.thinkific.com/api/api-documentation/#/Courses/getCourses
     * @param array $filters
     * @return PagedPaginator
     */
    public function courses(array $filters = []): PagedPaginator
    {
        return $this->connector
            ->paginate(new Courses($filters));
    }

    /**
     * Get the chapters of a Course
     * @see https://developers.thinkific.com/api/api-documentation/#/Courses/getChapterOfCourseByID
     * @param int $productable_id
     * @return PagedPaginator
     */
    public function chapters(int $productable_id): PagedPaginator
    {
        return $this->connector
            ->paginate(new Chapters($productable_id));
    }

}