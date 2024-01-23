<?php

namespace WooNinja\ThinkificSaloon\Requests\Webhooks;


use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\CreateWebhook;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\Webhook;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Create extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateWebhook $webhook
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "webhooks";
    }

    protected function defaultBody(): array
    {
        return [
            'topic' => $this->webhook->topic,
            'target_url' => $this->webhook->target_url,
        ];
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