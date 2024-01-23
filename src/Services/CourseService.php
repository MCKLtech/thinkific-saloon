<?php

namespace WooNinja\ThinkificSaloon\Services;


use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\Requests\Courses\Chapters;
use WooNinja\ThinkificSaloon\Requests\Courses\Courses;
use WooNinja\ThinkificSaloon\Requests\Courses\Get;


class CourseService extends Resource
{
    /**
     * Get a Course by its ID.
     * @see @see https://developers.thinkific.com/api/api-documentation/#/Courses/getCourseByID
     * @param int $productable_id
     * @return Course
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $productable_id): Course
    {
        return $this->connector
            ->send(new Get($productable_id))
            ->dtoOrFail();
    }

    /**
     * Get a list of Courses
     * @see https://developers.thinkific.com/api/api-documentation/#/Courses/getCourses
     * @return PagedPaginator
     */
    public function courses(): PagedPaginator
    {
        return $this->connector
            ->paginate(new Courses());
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