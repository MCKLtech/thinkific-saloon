<?php

namespace WooNinja\ThinkificSaloon\Requests\Courses;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Chapters\Chapter;
use WooNinja\ThinkificSaloon\DataTransferObjects\Courses\Course;

final class Chapters extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $productable_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "courses/{$this->productable_id}/chapters";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $chapter) {
            return new Chapter(
                id: $chapter['id'],
                name: $chapter['name'],
                position: $chapter['position'],
                description: $chapter['description'],
                duration_in_seconds: $chapter['duration_in_seconds'],
                content_ids: $chapter['content_ids'],
            );
        }, $response->json('items'));
    }
}