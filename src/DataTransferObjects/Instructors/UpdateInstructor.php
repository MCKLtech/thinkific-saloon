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
        public ?string $slug,
        public ?string $avatar_url,
    )
    {

    }
}