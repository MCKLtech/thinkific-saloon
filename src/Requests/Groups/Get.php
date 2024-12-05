<?php

namespace WooNinja\ThinkificSaloon\Requests\Groups;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Groups\Group;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $group_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "groups/{$this->group_id}";
    }

    public function createDtoFromResponse(Response $response): Group
    {
        $group = $response->json('group');

        return new Group(
            id: $group['id'],
            name: $group['name'],
            token: $group['token'],
            created_at: Carbon::parse($group['created_at'])
        );
    }
}