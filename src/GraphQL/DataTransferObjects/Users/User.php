<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users;

final class User
{
    public function __construct(
        public int    $id,
        public string $gid,
        public string $first_name,
        public string $last_name,
        public string $email,
        public ?bool  $has_admin_role,
        public ?array $custom_profile_fields,
    )
    {
    }

}