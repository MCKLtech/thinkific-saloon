<?php

namespace WooNinja\ThinkificSaloon\Requests\Webhooks;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class Delete extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly string $webhook_id,
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "webhooks/{$this->webhook_id}";
    }


}