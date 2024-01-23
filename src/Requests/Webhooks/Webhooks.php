<?php

namespace WooNinja\ThinkificSaloon\Requests\Webhooks;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\Webhook;

final class Webhooks extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return "webhooks";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $webhook) {
            return new Webhook(
                id: $webhook['id'],
                status: $webhook['status'],
                topic: $webhook['topic'],
                created_at: Carbon::parse($webhook['created_at']),
                created_by: $webhook['created_by'],
                updated_at: Carbon::parse($webhook['updated_at']),
                updated_by: $webhook['updated_by'],
                target_url: $webhook['target_url'],
            );
        }, $response->json('items'));
    }
}