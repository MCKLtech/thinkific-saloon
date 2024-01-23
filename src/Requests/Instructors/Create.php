<?php

namespace WooNinja\ThinkificSaloon\Requests\Instructors;


use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\Instructor;
use \WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\CreateInstructor;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Create extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateInstructor $createInstructor
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "instructors";
    }

    protected function defaultBody(): array
    {
        $body = [
            'user_id' => $this->createInstructor->user_id,
            'title' => $this->createInstructor->title,
            'first_name' => $this->createInstructor->first_name,
            'last_name' => $this->createInstructor->last_name,
            'bio' => $this->createInstructor->bio,
            'slug' => $this->createInstructor->slug,
            'avatar_url' => $this->createInstructor->avatar_url,
            'email' => $this->createInstructor->email,
        ];

        return $this->removeEmptyArrayValues($body);
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