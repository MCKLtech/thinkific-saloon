<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\OAuth;

use InvalidArgumentException;

class Refresh
{
    public function __construct(
        public string $client_id,
        public string $client_secret,
        public string $refresh_token,
        public string $subdomain,
    )
    {
        /**
         * The subdomain is interpolated directly into the token refresh URL
         * (see Requests\OAuth\Refresh::resolveEndpoint). Without this check, a
         * subdomain containing characters like '@', '#', '?' or '/' could redirect
         * the request - along with the client secret and refresh token - to a
         * host outside thinkific.com.
         */
        if (!preg_match('/^[a-z0-9-]+$/i', $subdomain)) {
            throw new InvalidArgumentException("Invalid subdomain '{$subdomain}'. Subdomains may only contain letters, numbers and hyphens.");
        }
    }

}