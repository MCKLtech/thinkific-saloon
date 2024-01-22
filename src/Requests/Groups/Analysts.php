<?php

namespace WooNinja\ThinkificSaloon\Requests\Groups;


use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

final class Analysts extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $group_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "groups/{$this->group_id}/analysts";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return $response->json('group_analysts');
    }
}