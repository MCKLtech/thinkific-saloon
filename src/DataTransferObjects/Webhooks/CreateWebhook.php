<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks;

class CreateWebhook
{
    public function __construct(
        public string $topic,
        public string $target_url,
    )
    {

    }
}