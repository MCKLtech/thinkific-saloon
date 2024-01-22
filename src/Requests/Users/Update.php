<?php

namespace WooNinja\ThinkificSaloon\Requests\Users;


use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\CreateUser;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\UpdateUser;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Update extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly UpdateUser $updateUser
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "users/{$this->updateUser->id}";
    }

    protected function defaultBody(): array
    {
        $body = [
            'first_name' => $this->updateUser->first_name,
            'last_name' => $this->updateUser->last_name,
            'email' => $this->updateUser->email,
            'password' => $this->updateUser->password,
            'custom_profile_fields' => $this->updateUser->custom_profile_fields,
            'roles' => $this->updateUser->roles,
            'bio' => $this->updateUser->bio,
            'avatar_url' => $this->updateUser->avatar_url,
            'company' => $this->updateUser->company,
            'headline' => $this->updateUser->headline,
            'external_source' => $this->updateUser->external_source,
            'affiliate_code' => $this->updateUser->affiliate_code,
            'affiliate_commission' => $this->updateUser->affiliate_commission,
            'affiliate_commission_type' => $this->updateUser->affiliate_commission_type,
            'affiliate_payout_email' => $this->updateUser->affiliate_payout_email,
        ];

        return $this->removeEmptyArrayValues($body);
    }

}