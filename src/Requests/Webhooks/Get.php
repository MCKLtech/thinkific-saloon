<?php

namespace WooNinja\ThinkificSaloon\Requests\Webhooks;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\Webhook;

final class Get extends Request
{

    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $webhook_id
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "webhooks/{$this->webhook_id}";
    }

    public function createDtoFromResponse(Response $response): Webhook
    {
        $script = $response->json();

        return new Webhook(
            id: $script['id'],
            status: $script['status'],
            topic: $script['topic'],
            created_at: Carbon::parse($script['created_at']),
            created_by: $script['created_by'],
            updated_at: Carbon::parse($script['updated_at']),
            updated_by: $script['updated_by'],
            target_url: $script['target_url'],
        );
    }
}