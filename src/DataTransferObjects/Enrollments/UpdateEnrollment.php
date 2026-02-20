<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\UpdateEnrollmentInterface;

final class UpdateEnrollment implements UpdateEnrollmentInterface
{
    public function __construct(
        public int|string $enrollment_id,
        public ?Carbon    $activated_at,
        public ?Carbon    $expiry_date,
        public ?int       $user_id = null,
        public ?int       $course_id = null
    )
    {
    }
}