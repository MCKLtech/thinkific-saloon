<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\OAuth;

use Carbon\Carbon;

class Token
{
    public function __construct(
        public string $access_token,
        public string $refresh_token,
        public string $token_type,
        public string $gid,
        public Carbon $expires_at
    )
    {
    }

}