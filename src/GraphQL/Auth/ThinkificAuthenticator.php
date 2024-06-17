<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Auth;

use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;
class ThinkificAuthenticator implements Authenticator
{
    public function __construct(
        private readonly string $token
    )
    {
    }

    public
    function set(PendingRequest $pendingRequest): void
    {
        $pendingRequest->headers()->add('Authorization', 'Bearer ' . $this->token);
    }

}