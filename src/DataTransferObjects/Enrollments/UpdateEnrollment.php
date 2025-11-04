<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\UpdateEnrollmentInterface;

final class UpdateEnrollment implements UpdateEnrollmentInterface
{
    public function __construct(
        int     $enrollment_id,
        ?Carbon $activated_at,
        ?Carbon $expiry_date,
        ?int    $user_id = null,
        ?int    $course_id = null
    )
    {
    }
}