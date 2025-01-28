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
        if (filter_var($this->gid_or_email, FILTER_VALIDATE_EMAIL)) {
            $path = 'data.userByEmail.groups.nodes';
        } else {
            $path = 'data.user.groups.nodes';
        }

        $groups = $response->json($path);

        if (empty($groups)) return [];

        return array_map(fn($group) => new Group(
            created_at: Carbon::parse($group['createdAt']),
            id: $group['id'],
            name: $group['name']
        ), $groups);
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
      nodes {
        name
        id
        createdAt
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
        nodes {
          createdAt
          id
          name
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