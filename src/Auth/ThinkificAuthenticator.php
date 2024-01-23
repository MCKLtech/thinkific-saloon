<?php

namespace WooNinja\ThinkificSaloon\Auth;

use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;
class ThinkificAuthenticator implements Authenticator
{

    public function __construct(
        private readonly string $api_key,
        private readonly string $subdomain,
        private readonly bool   $is_oauth = false
    )
    {
    }

    public
    function set(PendingRequest $pendingRequest): void
    {
        if ($this->is_oauth) {
            $pendingRequest->headers()->add('Authorization', 'Bearer ' . $this->api_key);
        } else {
            $pendingRequest->headers()->add('X-Auth-API-Key', $this->api_key);
            $pendingRequest->headers()->add('X-Auth-Subdomain', $this->subdomain);
        }
    }

}