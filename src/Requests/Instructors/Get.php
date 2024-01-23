<?php

namespace WooNinja\ThinkificSaloon\Requests\Instructors;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\Instructor;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;

final class Get extends Request
{

    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $instructor_id,
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "instructors/{$this->instructor_id}";
    }

    public function createDtoFromResponse(Response $response): Instructor
    {
        $instructor = $response->json();

        return new Instructor(
            id: $instructor['id'],
            user_id: $instructor['user_id'],
            title: $instructor['title'],
            first_name: $instructor['first_name'],
            last_name: $instructor['last_name'],
            bio: $instructor['bio'],
            slug: $instructor['slug'],
            avatar_url: $instructor['avatar_url'],
            email: $instructor['email'],
            created_at: Carbon::parse($instructor['created_at']),
        );
    }
}