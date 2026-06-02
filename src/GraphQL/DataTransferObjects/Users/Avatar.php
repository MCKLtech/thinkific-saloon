<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users;

final class Avatar
{
    public function __construct(
        public string  $url,
        public ?string $alt_text = null,
    ) {
    }
}