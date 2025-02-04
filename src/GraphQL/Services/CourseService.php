<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Courses\Course;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Courses\Courses;

class CourseService extends Resource
{
    /**
     * Return the Courses
     *
     * @param int $per_page
     * @return Paginator
     */
    public function courses(int $per_page = 100): Paginator
    {
        $users = new Courses($per_page);

        return $users->paginate($this->connector);
    }

    /**
     * Get the Chapters and Lessons for a Course
     *
     * @param int $course_id
     * @param int $per_page
     * @return Paginator
     */
    public function chapters(int $course_id, int $per_page = 100): Paginator
    {
        $course = new Course($course_id, $per_page);

        return $course->paginate($this->connector);
    }

}