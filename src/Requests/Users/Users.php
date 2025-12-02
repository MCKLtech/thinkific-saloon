<?php

namespace WooNinja\ThinkificSaloon\Requests\Users;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;

final class Users extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly array $filters = [],
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "users";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $user) {
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
                created_at: !empty($user['created_at']) ? Carbon::parse($user['created_at']) : null,
            );
        }, $response->json('items'));
    }

    protected function defaultQuery(): array
    {
        return $this->filters;
    }
}