<?php

namespace WooNinja\ThinkificSaloon\Requests\Chapters;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

use WooNinja\ThinkificSaloon\DataTransferObjects\Chapters\Chapter;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $chapter_id
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "chapters/{$this->chapter_id}";
    }

    public function createDtoFromResponse(Response $response): Chapter
    {
        $responseData = $response->json();

        return new Chapter(
            id: $responseData['id'],
            name: $responseData['name'],
            position: $responseData['position'],
            description: $responseData['description'],
            duration_in_seconds: $responseData['duration_in_seconds'],
            content_ids: $responseData['content_ids'],
        );
    }
}