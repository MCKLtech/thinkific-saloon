<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Users;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class Get extends Request implements HasBody
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public function __construct(
        private readonly string $gid
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): User
    {

        $user = $response->json('data.user');

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
            query UserByGid($gid: ID!, $first: Int) {
  user(gid: $gid) {
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
                'gid' => $this->gid,
                'first' => 100
            ]
        ];

    }
}