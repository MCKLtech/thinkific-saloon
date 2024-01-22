<?php

namespace WooNinja\ThinkificSaloon\Requests\Groups;


use Saloon\Enums\Method;
use Saloon\Http\Request;

final class RemoveAnalyst extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $group_id,
        private readonly int $user_id
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "groups/{$this->group_id}/analysts/{$this->user_id}";
    }

}