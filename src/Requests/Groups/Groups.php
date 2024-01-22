<?php

namespace WooNinja\ThinkificSaloon\Requests\Groups;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Groups\Group;

final class Groups extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return "groups";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $group) {
            return new Group(
                id: $group['id'],
                name: $group['name'],
                token: $group['token'],
                created_at: Carbon::parse($group['created_at'])
            );
        }, $response->json('items'));
    }
}