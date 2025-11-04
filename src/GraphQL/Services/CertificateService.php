<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Certificates\CertificatesForCourse;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Certificates\CertificatesForUser;

class CertificateService extends Resource
{
    /**
     * Return a list of certificates for a given course
     *
     * @param string $courseName
     * @param int $per_page
     * @return Paginator
     */
    public function certificatesByCourse(string $courseName, int $per_page = 50): Paginator
    {
        $certificates = new CertificatesForCourse($courseName, $per_page);

        return $certificates->paginate($this->connector);
    }

    /**
     * Return a list of certificates for a given user email
     *
     * @param string $email
     * @param int $per_page
     * @return Paginator
     */
    public function certificatesByUserEmail(string $email, int $per_page = 50): Paginator
    {
        $certificates = new CertificatesForUser($email, $per_page);

        return $certificates->paginate($this->connector);
    }


}