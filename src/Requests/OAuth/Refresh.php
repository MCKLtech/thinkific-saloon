<?php

namespace WooNinja\ThinkificSaloon\Requests\OAuth;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasJsonBody;
use \WooNinja\ThinkificSaloon\DataTransferObjects\OAuth\Refresh as RefreshDTO;
use WooNinja\ThinkificSaloon\DataTransferObjects\OAuth\Token;

class Refresh extends SoloRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public RefreshDTO $refresh,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "https://" . $this->refresh->subdomain . ".thinkific.com/oauth2/token";

    }

    protected function defaultBody(): array
    {
        return [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh->refresh_token
        ];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode("{$this->refresh->client_id}:{$this->refresh->client_secret}")
        ];
    }

    public function createDtoFromResponse(Response $response): Token
    {
        $token = $response->json();

        return new Token(
            access_token: $token['access_token'],
            refresh_token: $token['refresh_token'],
            token_type: $token['token_type'],
            gid: $token['gid'],
            expires_at: Carbon::now()->addSeconds($token['expires_in'])
        );

    }
}