<?php

namespace WooNinja\ThinkificSaloon\Requests\Users;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;

final class Get extends Request
{

    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $user_id
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "users/{$this->user_id}";
    }

    public function createDtoFromResponse(Response $response): User
    {
        $user = $response->json();

        return new User(
            id: $user['id'],
            first_name: $user['first_name'],
            last_name: $user['last_name'],
            email: $user['email'],
            password: $user['password'] ?? null,
            roles: $user['roles'],
            avatar_url: $user['avatar_url'],
            bio: $user['bio'],
            company: $user['company'],
            headline: $user['headline'],
            external_source: $user['external_source'],
            affiliate_code: $user['affiliate_code'],
            affiliate_commission: $user['affiliate_commission'],
            affiliate_commission_type: $user['affiliate_commission_type'],
            affiliate_payout_email: $user['affiliate_payout_email'],
            custom_profile_fields: $user['custom_profile_fields'],
        );
    }
}