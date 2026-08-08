<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Instructors;

class UpdateInstructor
{
    public function __construct(
        public int $id,
        public string $first_name,
        public string $last_name,
        public ?string $email,
        public ?string $title,
        public ?int $user_id,
        public ?string $bio,
        /**
         * Required by Thinkific's InstructorRequest schema (shared between
         * create and update), unlike every other field here - omitting it
         * produces a 422 from the real API rather than a client-side error,
         * so it's non-nullable here to fail fast instead.
         */
        public string $slug,
        public ?string $avatar_url,
    )
    {

    }
}