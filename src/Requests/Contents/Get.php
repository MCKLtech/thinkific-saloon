<?php

namespace WooNinja\ThinkificSaloon\Requests\Contents;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

use WooNinja\ThinkificSaloon\DataTransferObjects\Contents\Content;


final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $content_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "contents/{$this->content_id}";
    }

    public function createDtoFromResponse(Response $response): Content
    {
        $responseData = $response->json();

        return new Content(
            id: $responseData['id'],
            name: $responseData['name'],
            position: $responseData['position'],
            chapter_id: $responseData['chapter_id'],
            contentable_type: $responseData['contentable_type'],
            free: $responseData['free'],
            take_url: $responseData['take_url']
        );
    }
}