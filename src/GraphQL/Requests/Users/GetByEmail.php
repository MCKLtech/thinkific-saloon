<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Users;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\Avatar;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class GetByEmail extends Request implements HasBody
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public function __construct(
        private readonly string $email,
        private readonly int $custom_fields_per_page = 50
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): User
    {
        $user = $response->json('data.userByEmail');

        return new User(
            id: $user['id'],
            email: $user['email'],
            gid: $user['gid'],
            first_name: $user['firstName'],
            last_name: $user['lastName'],
            has_admin_role: $user['hasAdminRole'],
            custom_profile_fields: $user['customProfileFields'],
            avatar: isset($user['profile']['avatar'])
                ? new Avatar(
                    url: $user['profile']['avatar']['url'],
                    alt_text: $user['profile']['avatar']['altText'] ?? null,
                )
                : null,
        );
    }

    protected function defaultBody(): array
    {
        return [
            'query' => '
        query UserByEmail($email: EmailAddress!, $first: Int) {
  userByEmail(email: $email) {
    id
    gid
    email
    firstName
     lastName
    hasAdminRole
    customProfileFields(first: $first) {
      edges {
        cursor
        node {
          id
          label
          required
          type
          typeId
          value
        }
      }
    }
    profile {
      avatar {
        altText
        url
      }
    }
  }
}
    ',
            'variables' => [
                'email' => $this->email,
                'first' => $this->custom_fields_per_page
            ]
        ];

    }
}