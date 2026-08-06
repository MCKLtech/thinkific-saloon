<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use WooNinja\ThinkificSaloon\DataTransferObjects\OAuth\Refresh;
use WooNinja\ThinkificSaloon\DataTransferObjects\OAuth\Token;
use WooNinja\ThinkificSaloon\Requests\OAuth\Refresh as RefreshRequest;
use WooNinja\ThinkificSaloon\Tests\TestCase;
use Saloon\Http\Faking\MockResponse;

class OAuthServiceTest extends TestCase
{
    public function test_can_refresh_an_oauth_token(): void
    {
        $this->mockGlobalRequests([
            RefreshRequest::class => MockResponse::make([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'token_type' => 'bearer',
                'gid' => 'abc123',
                'expires_in' => 82800,
            ], 200),
        ]);

        $token = $this->service->oauth->refresh(new Refresh(
            client_id: 'client-id',
            client_secret: 'client-secret',
            refresh_token: 'old-refresh-token',
            subdomain: 'acme-university',
        ));

        $this->assertInstanceOf(Token::class, $token);
        $this->assertEquals('new-access-token', $token->access_token);
        $this->assertEquals('new-refresh-token', $token->refresh_token);
        $this->assertEquals('abc123', $token->gid);
    }
}
