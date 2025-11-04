<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Webhooks\WebhookInterface;

class Webhook implements WebhookInterface
{
    public function __construct(
        public string $id,
        public string $status,
        public string $topic,
        public Carbon $created_at,
        public string $created_by,
        public Carbon $updated_at,
        public string $updated_by,
        public string $target_url,
    )
    {

    }
}