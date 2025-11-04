<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\CreateEnrollmentInterface;

final class CreateEnrollment implements CreateEnrollmentInterface
{
    public function __construct(
        public int    $user_id,
        public int    $course_id,
        public ?Carbon $activated_at,
        public ?Carbon $expiry_date,
    )
    {
    }
}