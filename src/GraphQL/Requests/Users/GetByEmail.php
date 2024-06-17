<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Users;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class GetByEmail extends Request implements HasBody
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public function __construct(
        private readonly string $email
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
            gid: $user['gid'],
            first_name: $user['firstName'],
            last_name: $user['lastName'],
            email: $user['email'],
            has_admin_role: $user['hasAdminRole'],
            custom_profile_fields: $user['customProfileFields'],
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
                'first' => 100
            ]
        ];

    }
}