<?php

namespace WooNinja\ThinkificSaloon\Requests\Instructors;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\Instructor;

final class Instructors extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return "instructors";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $instructor) {
            return new Instructor(
                id: $instructor['id'],
                user_id: $instructor['user_id'] ?? null,
                title: $instructor['title'] ?? null,
                first_name: $instructor['first_name'],
                last_name: $instructor['last_name'],
                bio: $instructor['bio'] ?? null,
                slug: $instructor['slug'] ?? '',
                avatar_url: $instructor['avatar_url'] ?? null,
                email: $instructor['email'] ?? null,
                created_at: Carbon::parse($instructor['created_at']),
            );
        }, $response->json('items'));
    }
}