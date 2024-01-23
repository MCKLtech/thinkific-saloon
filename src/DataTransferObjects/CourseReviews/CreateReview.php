<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\CourseReviews;

class CreateReview
{
    public function __construct(
        public int $course_id,
        public int $rating,
        public string $title,
        public string $review_text,
        public int $user_id,
        public bool $approved

    )
    {

    }
}