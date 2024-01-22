<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments;

use Carbon\Carbon;

final class UpdateEnrollment
{
    public function __construct(
        public int    $enrollment_id,
        public ?Carbon $activated_at,
        public ?Carbon $expiry_date,
    )
    {
    }
}