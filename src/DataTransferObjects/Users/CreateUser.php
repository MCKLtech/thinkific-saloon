<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Users;

class CreateUser
{

    public function __construct(
        public string  $first_name,
        public string  $last_name,
        public string  $email,
        public ?string $password,
        public bool    $skip_custom_fields_validation,
        public bool    $send_welcome_email,
        public ?array  $custom_profile_fields,
        public ?array  $roles,
        public ?string $bio,
        public ?string $company,
        public ?string $headline,
        public ?string $affiliate_code,
        public ?int    $affiliate_commission,
        public ?string $affiliate_commission_type,
        public ?string $affiliate_payout_email,
        public ?string $external_id,
        public ?string $provider,
    )
    {
    }

}