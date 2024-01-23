<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Instructors;

use Carbon\Carbon;

class CreateInstructor
{
    public function __construct(
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