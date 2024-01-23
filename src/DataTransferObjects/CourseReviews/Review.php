<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews;

use Carbon\Carbon;

class Review
{
    public function __construct(
        public int $id,
        public int $rating,
        public string $title,
        public string $review_text,
        public int $user_id,
        public int $course_id,
        public bool $approved,
        public Carbon $created_at
    )
    {

    }
}