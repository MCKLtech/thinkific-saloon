<?php

namespace WooNinja\ThinkificSaloon\Services;

use Carbon\Carbon;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\Bundle;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\CreateBundleEnrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\UpdateBundleEnrollment;
use WooNinja\ThinkificSaloon\Requests\Bundles\Courses;
use WooNinja\ThinkificSaloon\Requests\Bundles\CreateEnrollment;
use WooNinja\ThinkificSaloon\Requests\Bundles\Enrollments;
use WooNinja\ThinkificSaloon\Requests\Bundles\Get;
use WooNinja\ThinkificSaloon\Requests\Bundles\UpdateEnrollment;

class BundleService extends Resource
{
    /**
     * Get a Bundle by its ID.
     * @see https://developers.thinkific.com/api/api-documentation/#/Bundles/getBundleByID
     * @param int $productable_id
     * @return Bundle
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $productable_id): Bundle
    {
        return $this->connector
            ->send(new Get($productable_id))
            ->dtoOrFail();
    }

    /**
     * Get the Courses of a Bundle
     * @see https://developers.thinkific.com/api/api-documentation/#/Bundles/get_bundles__id__courses
     * @param int $productable_id
     * @return PagedPaginator
     */
    public function courses(int $productable_id): PagedPaginator
    {
        return $this->connector
            ->paginate(new Courses($productable_id));
    }

    /**
     * Get the enrollments of a Bundle. Optional filters can be passed.
     * @see https://developers.thinkific.com/api/api-documentation/#/Bundles/getBundleEnrollments
     * @param int $productable_id
     * @param array $filters
     * @return PagedPaginator
     */
    public function enrollments(int $productable_id, array $filters = []): PagedPaginator
    {
        return $this->connector
            ->paginate(new Enrollments($productable_id, $filters));
    }

    /**
     * Create an enrollment in a bundle
     * @see https://developers.thinkific.com/api/api-documentation/#/Bundles/createEnrollmentInBundle
     *
     * @param CreateBundleEnrollment $createBundleEnrollment
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function createEnrollment(CreateBundleEnrollment $createBundleEnrollment): Response
    {
        return $this->connector
            ->send(new CreateEnrollment($createBundleEnrollment));
    }

    /**
     * Create an enrollment in a bundle
     * @see https://developers.thinkific.com/api/api-documentation/#/Bundles/updateEnrollmentsInBundle
     *
     * @param UpdateBundleEnrollment $updateBundleEnrollment
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function updateEnrollment(UpdateBundleEnrollment $updateBundleEnrollment): Response
    {
        return $this->connector
            ->send(new UpdateEnrollment($updateBundleEnrollment));
    }

    /**
     * Expire an enrollment in a bundle by setting the expiry to the current date.
     *
     * @param int $productable_id
     * @param int $user_id
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function expireEnrollment(int $productable_id, int $user_id): Response
    {
        $enrollment = new UpdateBundleEnrollment(
            productable_id: $productable_id,
            user_id: $user_id,
            activated_at: null,
            expiry_date: Carbon::now()->startOfDay()->subSecond(),
        );

        return $this->connector
            ->send(new UpdateEnrollment($enrollment));
    }

    /**
     * Determine if a user is part of a bundle by counting their enrollments
     *
     * @param int $productable_id
     * @param int|string $user_id_or_email
     * @return bool
     */
    public function isUserEnrolled(int $productable_id, int|string $user_id_or_email, array $filters = []): bool
    {
        if (is_numeric($user_id_or_email)) {
            $filters = [
                'query[user_id]' => $user_id_or_email,
            ];
        } else {
            $filters = [
                'query[email]' => $user_id_or_email,
            ];
        }

        /**
         * Avoid Excessive API Calls by limiting to 100
         */
        $filters['limit'] = 100;

        $enrollments = $this->enrollments($productable_id, $filters);

        return $enrollments->collect()->count() > 0;
    }
}