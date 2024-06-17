<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Users;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Groups\Group;

final class Groups extends Request implements HasBody
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public function __construct(
        private readonly string $gid_or_email
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        $group = $response->json('data.user.groups.edges');

        if(empty($group)) return [];

        return array_map(fn($group) => new Group(
            created_at: Carbon::parse($group['node']['createdAt']),
            id: $group['node']['id'],
            name: $group['node']['name']
        ), $group);
    }

    protected function defaultBody(): array
    {

        if (filter_var($this->gid_or_email, FILTER_VALIDATE_EMAIL)) {

            return [
                'query' => '
        query UserByEmail($email: EmailAddress!) {
  userByEmail(email: $email) {
    email
    firstName
    lastName
    gid
    id
    groups(first: 100) {
      edges {
        node {
          createdAt
          id
          name
        }
      }
    }
  }
}',
                'variables' => [
                    'email' => $this->gid_or_email,
                    'first' => 100
                ]
            ];

        } else {

            return [
                'query' => '
        query UserGroups($gid: ID!) {
  user(gid: $gid) {
    email
    firstName
    lastName
    gid
    id
    groups(first: 100) {
      edges {
        node {
          createdAt
          id
          name
        }
      }
    }
  }
}',
                'variables' => [
                    'gid' => $this->gid_or_email,
                    'first' => 100
                ]
            ];
        }
    }
}