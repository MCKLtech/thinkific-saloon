<?php

namespace WooNinja\ThinkificSaloon\Requests\Groups;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class AssignAnalysts extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int   $group_id,
        private readonly array $user_ids
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "groups/{$this->group_id}/analysts";
    }

    protected function defaultBody(): array
    {
        return [
            'user_ids' => $this->user_ids
        ];
    }

}