<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Instructors;

use Carbon\Carbon;

class Instructor
{
    public function __construct(
        public int $id,
        public ?int $user_id,
        public ?string $title,
        public string $first_name,
        public string $last_name,
        public ?string $bio,
        public string $slug,
        public ?string $avatar_url,
        public ?string $email,
        public Carbon $created_at

    )
    {

    }


}