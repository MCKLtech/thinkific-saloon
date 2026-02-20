<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\CreateEnrollmentInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Enrollments\ReadEnrollmentInterface;

final class ReadEnrollment implements ReadEnrollmentInterface
{

    public function __construct(
        public int|string $enrollment_id,
        public ?int       $user_id,
        public ?int       $course_id,
    )
    {
    }
}