<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Users;

final class User
{

    public function __construct(
        public int     $id,
        public string  $first_name,
        public string  $last_name,
        public string  $email,
        public ?string $password,
        public array   $roles,
        public ?string $avatar_url,
        public ?string $bio,
        public ?string $company,
        public ?string $headline,
        public ?string $external_source,
        public ?string $affiliate_code,
        public ?int    $affiliate_commission,
        public ?string $affiliate_commission_type,
        public ?string $affiliate_payout_email,
        public ?array  $custom_profile_fields,
    )
    {
    }

    public function getFullName(): string
    {
        $fullName = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));

        return !empty($fullName) ? $fullName : $this->email;
    }

}