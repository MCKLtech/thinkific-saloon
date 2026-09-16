<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Responses;

use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\GraphQL\Exceptions\GraphQLException;

class ThinkificGraphQLResponse extends Response
{
    /**
     * The top-level `errors` array, or [] when the response has none.
     *
     * Thinkific returns these with HTTP 200; ThinkificConnector::hasRequestFailed()
     * treats a populated array as a failed request so AlwaysThrowOnErrors fires.
     *
     * @return array<int, array<string, mixed>>
     */
    public function graphQLErrors(): array
    {
        return GraphQLException::extractErrors($this);
    }

    public function hasGraphQLErrors(): bool
    {
        return $this->graphQLErrors() !== [];
    }
}
