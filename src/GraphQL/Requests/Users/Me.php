<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Users;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

final class Me extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '';
    }

    protected function defaultBody(): array
    {
        return [
            'query' => 'query Me {
  me {
    id
  }
}',
        ];
    }

    /**
     * GraphQL errors (e.g. an invalid/expired token) arrive as HTTP 200 with
     * an `errors` array rather than a failed status code, so a successful
     * Saloon response alone doesn't mean the request actually succeeded.
     */
    public function createDtoFromResponse(Response $response): bool
    {
        if (!empty($response->json('errors'))) {
            return false;
        }

        return $response->json('data.me.id') !== null;
    }
}
