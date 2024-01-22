<?php

namespace WooNinja\ThinkificSaloon\Requests\Groups;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Groups\Group;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Create extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $name
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "groups";
    }


    protected function defaultBody(): array
    {
        return ['name' => $this->name];
    }


    public function createDtoFromResponse(Response $response): Group
    {
        $group = $response->json('group');

        return new Group(
            id: $group['id'],
            name: $group['name'],
            token: $group['token'],
            created_at: Carbon::parse($group['created_at'])
        );
    }

}