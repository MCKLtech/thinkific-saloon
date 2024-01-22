<?php

namespace WooNinja\ThinkificSaloon\Services;

use Carbon\Carbon;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\CreateEnrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\Enrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\UpdateEnrollment;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Create;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Enrollments;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Get;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Update;

class EnrollmentService extends Resource
{

    /**
     * Get an Enrollment by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Enrollments/getEnrollmentsByID
     *
     * @param int $enrollment_id
     * @return Enrollment
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $enrollment_id): Enrollment
    {
        return $this->connector
            ->send(new Get($enrollment_id))
            ->dtoOrFail();
    }

    /**
     * List enrollments. Optional filters can be passed.
     *
     * @see https://developers.thinkific.com/api/api-documentation/#/Enrollments/getEnrollments
     * @param array $filters
     * @return PagedPaginator
     */
    public function enrollments(array $filters = []): PagedPaginator
    {
        return $this->connector
            ->paginate(new Enrollments($filters));
    }

    /**
     * Create an Enrollment
     * @https://developers.thinkific.com/api/api-documentation/#/Enrollments/createEnrollment
     *
     * @param CreateEnrollment $enrollment
     * @return Enrollment
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateEnrollment $enrollment): Enrollment
    {
        return $this->connector
            ->send(new Create($enrollment))
            ->dtoOrFail();
    }

    /**
     * Update an Enrollment
     * @see https://developers.thinkific.com/api/api-documentation/#/Enrollments/updateEnrollment
     *
     * @param UpdateEnrollment $enrollment
     * @return Enrollment
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function update(UpdateEnrollment $enrollment): Enrollment
    {
        return $this->connector
            ->send(new Update($enrollment))
            ->dtoOrFail();
    }

    /**
     * Expire an Enrollment
     *
     * @param int $enrollment_id
     * @return Enrollment
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function expire(int $enrollment_id): Enrollment
    {
        $enrollment = new UpdateEnrollment(
            enrollment_id: $enrollment_id,
            activated_at: null,
            expiry_date: Carbon::now()
        );

        return $this->update($enrollment);
    }

    /**
     * Find enrollments for a given Course
     *
     * @param int $course_id
     * @param array $filters
     * @return PagedPaginator
     */
    public function enrollmentsForCourse(int $course_id, array $filters = []): PagedPaginator
    {
        return $this->enrollments(array_merge(['query[course_id]' => $course_id], $filters));
    }

    /**
     * Find Enrollments for a given User
     *
     * @param int|string $user_id_or_email
     * @param array $filters
     * @return PagedPaginator
     */
    public function enrollmentsForUser(int|string $user_id_or_email, array $filters = []): PagedPaginator
    {
        return $this->enrollments(array_merge($this->getUserFilter($user_id_or_email), $filters));
    }

    /**
     * Find Enrollments for a given User in a given Course
     *
     * @param int|string $user_id_or_email
     * @param int $course_id
     * @param array $filters
     * @return PagedPaginator
     */
    public function enrollmentsForUserInCourse(int|string $user_id_or_email, int $course_id, array $filters = []): PagedPaginator
    {
        $filter = $this->getUserFilter($user_id_or_email);
        $filter['query[course_id]'] = $course_id;

        return $this->enrollments(array_merge($filter, $filters));
    }

    /**
     * Determine if user has existing enrollment in a course
     *
     * @param int|string $user_id_or_email
     * @param int $course_id
     * @param array $filters
     * @return bool
     */
    public function isUserEnrolledInCourse(int|string $user_id_or_email, int $course_id, array $filters = []): bool
    {
        return $this->enrollmentsForUserInCourse($user_id_or_email, $course_id, $filters)->count() > 0;
    }

    /**
     * Determine the Thinkific Query Filter for finding a user based on ID or Email
     *
     * @param int|string $user_id_or_email
     * @return array
     */
    private function getUserFilter(int|string $user_id_or_email): array
    {
        if (is_numeric($user_id_or_email))
            return ['query[user_id]' => $user_id_or_email];
        else
            return ['query[email]' => $user_id_or_email];
    }


}