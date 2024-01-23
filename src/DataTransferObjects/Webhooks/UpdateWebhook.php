<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks;

class UpdateWebhook
{
    public function __construct(
        public string    $id,
        public string $topic,
        public string $target_url,
    )
    {

    }
}