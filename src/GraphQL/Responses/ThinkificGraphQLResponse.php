<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Responses;

use Saloon\Http\Response;

class ThinkificGraphQLResponse extends Response
{
    // Rate-limit detection is handled by ThinkificConnector::handleTooManyAttempts().
}