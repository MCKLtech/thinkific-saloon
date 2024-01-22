<?php

namespace WooNinja\ThinkificSaloon\Requests\Chapters;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Contents\Content;

final class Contents extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $chapter_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "chapters/{$this->chapter_id}/content";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $content) {
            return new Content(
                id: $content['id'],
                name: $content['name'],
                position: $content['position'],
                chapter_id: $content['chapter_id'],
                contentable_type: $content['contentable_type'],
                free: $content['free'],
                take_url: $content['take_url']
            );
        }, $response->json('items'));
    }
}