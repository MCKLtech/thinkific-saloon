<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments;

use Carbon\Carbon;

final class Enrollment
{
    public function __construct(
        public int     $id,
        public string  $user_email,
        public string  $user_name,
        public int     $user_id,
        public string  $course_name,
        public int     $course_id,
        public float   $percentage_completed,
        public bool    $expired,
        public bool    $is_free_trial,
        public bool    $completed,
        public ?Carbon $started_at,
        public ?Carbon $activated_at,
        public ?Carbon $completed_at,
        public Carbon  $updated_at,
        public ?Carbon $expiry_date,

        /**
         * @var string|null
         * Only available from the enrollment.completed webhook at present (Dec/2024)
         */
        public ?string $credential_id,

        /**
         * @var string|null
         * Only available from the enrollment.completed webhook at present (Dec/2024)
         */
        public ?string $certificate_url,

        /**
         * @var Carbon|null
         * Only available from the enrollment.completed webhook at present (Dec/2024)
         * Unlike other dates in Thinkific, this date format is Y-m-d
         */
        public ?Carbon $certificate_expiry_date,
    )
    {
    }
}