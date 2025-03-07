<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users;

final class User
{
    public function __construct(
        public int    $id,
        public string $email,
        public ?string $gid = null,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?bool  $has_admin_role = null,
        public ?array $custom_profile_fields = null,
    ) {
    }
}