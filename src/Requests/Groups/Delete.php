<?php

namespace WooNinja\ThinkificSaloon\Requests\Groups;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class Delete extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $group_id
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "groups/{$this->group_id}";
    }


}