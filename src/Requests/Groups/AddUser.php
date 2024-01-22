<?php

namespace WooNinja\ThinkificSaloon\Requests\Groups;


use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class AddUser extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $user_id,
        private readonly array $group_names
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "group_users";
    }


    protected function defaultBody(): array
    {
        return [
            'user_id' => $this->user_id,
            'group_names' => $this->group_names
        ];
    }
}