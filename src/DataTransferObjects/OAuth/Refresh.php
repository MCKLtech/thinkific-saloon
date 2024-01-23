<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\OAuth;

class Refresh
{
    public function __construct(
        public string $client_id,
        public string $client_secret,
        public string $refresh_token,
        public string $subdomain,
    )
    {
    }

}