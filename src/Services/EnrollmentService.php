<?php

namespace WooNinja\ThinkificSaloon\Services;

use Carbon\Carbon;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\DeleteEnrollmentInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\ReadEnrollmentInterface;
use WooNinja\LMSContracts\Contracts\Services\EnrollmentServiceInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\EnrollmentInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\CreateEnrollmentInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\UpdateEnrollmentInterface;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\CreateEnrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\Enrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\UpdateEnrollment;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Create;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Enrollments;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Get;
use WooNinja\ThinkificSaloon\Requests\Enrollments\Update;

class EnrollmentService extends Resource implements EnrollmentServiceInterface
{

    /**
     * Get an Enrollment by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Enrollments/getEnrollmentsByID
     *
     * @param ReadEnrollmentInterface $enrollment_id
     * @return EnrollmentInterface
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(ReadEnrollmentInterface $enrollment_id): EnrollmentInterface
    {
        return $this->connector
            ->send(new Get($enrollment_id->enrollment_id))
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
     * @param CreateEnrollmentInterface $enrollment
     * @return EnrollmentInterface
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateEnrollmentInterface $enrollment): EnrollmentInterface
    {
        return $this->connector
            ->send(new Create($enrollment))
            ->dtoOrFail();
    }

    /**
     * Update an Enrollment
     * @see https://developers.thinkific.com/api/api-documentation/#/Enrollments/updateEnrollment
     *
     * @param UpdateEnrollmentInterface $enrollment
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function update(UpdateEnrollmentInterface $enrollment): Response
    {
        return $this->connector
            ->send(new Update($enrollment));
    }

    /**
     * Expire an Enrollment
     *
     * @param DeleteEnrollmentInterface $enrollment_id
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function expire(DeleteEnrollmentInterface $enrollment_id): Response
    {
        $enrollment = new UpdateEnrollment(
            enrollment_id: $enrollment_id->enrollment_id,
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
        return $this->enrollmentsForUserInCourse($user_id_or_email, $course_id, array_merge($filters, ['limit' => 1]))
                ->collect()
                ->count() > 0;
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