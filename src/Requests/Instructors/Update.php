<?php

namespace WooNinja\ThinkificSaloon\Requests\Instructors;


use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\Instructor;
use \WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\CreateInstructor as CreateInstructorDto;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\UpdateInstructor;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Update extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly UpdateInstructor $updateInstructor
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "instructors/{$this->updateInstructor->id}";
    }

    protected function defaultBody(): array
    {
        $body = [
            'user_id' => $this->updateInstructor->user_id,
            'title' => $this->updateInstructor->title,
            'first_name' => $this->updateInstructor->first_name,
            'last_name' => $this->updateInstructor->last_name,
            'bio' => $this->updateInstructor->bio,
            'slug' => $this->updateInstructor->slug,
            'avatar_url' => $this->updateInstructor->avatar_url,
            'email' => $this->updateInstructor->email,
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