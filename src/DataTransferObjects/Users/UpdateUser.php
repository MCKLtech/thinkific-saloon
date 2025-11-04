<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Users;

use WooNinja\LMSContracts\Contracts\DTOs\Users\UpdateUserInterface;

class UpdateUser implements UpdateUserInterface
{
    public function __construct(
        public int     $id,
        public string  $first_name,
        public string  $last_name,
        public string  $email,
        public ?string $password,
        public ?array  $custom_profile_fields,
        public ?array  $roles,
        public ?string $bio,
        public ?string $avatar_url,
        public ?string $company,
        public ?string $headline,
        public ?string $external_source,
        public ?string $affiliate_code,
        public ?int    $affiliate_commission,
        public ?string $affiliate_commission_type,
        public ?string $affiliate_payout_email
    )
    {
    }

}