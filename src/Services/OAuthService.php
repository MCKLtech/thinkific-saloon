<?php

namespace WooNinja\ThinkificSaloon\Services;

use WooNinja\ThinkificSaloon\DataTransferObjects\OAuth\Refresh;
use WooNinja\ThinkificSaloon\DataTransferObjects\OAuth\Token;
use WooNinja\ThinkificSaloon\Requests\OAuth\Refresh as RefreshRequest;


class OAuthService extends Resource
{
    /**
     * Refresh an OAuth token.
     * @see https://support.thinkific.dev/hc/en-us/articles/4422658129175-OAuth-Authorization
     *
     * @param Refresh $refresh
     * @return Token
     */
    public function refresh(Refresh $refresh): Token
    {
        $request = new RefreshRequest($refresh);
        return $request->send()->dtoOrFail();

    }

}