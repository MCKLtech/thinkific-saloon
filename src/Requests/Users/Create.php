<?php

namespace WooNinja\ThinkificSaloon\Requests\Users;


use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\CreateUser;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Create extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateUser $createUser
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "users";
    }

    protected function defaultBody(): array
    {
        $body = [
            'first_name' => $this->createUser->first_name,
            'last_name' => $this->createUser->last_name,
            'email' => $this->createUser->email,
            'password' => $this->createUser->password,
            'roles' => $this->createUser->roles,
            'bio' => $this->createUser->bio,
            'company' => $this->createUser->company,
            'headline' => $this->createUser->headline,
            'affiliate_code' => $this->createUser->affiliate_code,
            'affiliate_commission' => $this->createUser->affiliate_commission,
            'affiliate_commission_type' => $this->createUser->affiliate_commission_type,
            'affiliate_payout_email' => $this->createUser->affiliate_payout_email,
            'custom_profile_fields' => $this->createUser->custom_profile_fields,
            'skip_custom_fields_validation' => $this->createUser->skip_custom_fields_validation,
            'send_welcome_email' => $this->createUser->send_welcome_email,
            'external_id' => $this->createUser->external_id,
            'provider' => $this->createUser->provider
        ];

        return $this->removeEmptyArrayValues($body);
    }


    public function createDtoFromResponse(Response $response): User
    {
        $user = $response->json();

        return new User(
            id: $user['id'],
            first_name: $user['first_name'],
            last_name: $user['last_name'],
            email: $user['email'],
            password: $user['password'] ?? null,
            roles: $user['roles'],
            avatar_url: $user['avatar_url'],
            bio: $user['bio'],
            company: $user['company'],
            headline: $user['headline'],
            external_source: $user['external_source'],
            affiliate_code: $user['affiliate_code'],
            affiliate_commission: $user['affiliate_commission'],
            affiliate_commission_type: $user['affiliate_commission_type'],
            affiliate_payout_email: $user['affiliate_payout_email'],
            custom_profile_fields: $user['custom_profile_fields'],
        );
    }

}