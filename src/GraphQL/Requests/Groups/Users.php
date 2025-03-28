<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Groups;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\HasRequestPagination;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use Saloon\PaginationPlugin\CursorPaginator;
use Saloon\PaginationPlugin\Paginator;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class Users extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    public ?string $after = null;

    use HasJsonBody;

    public function __construct(
        private readonly string $group_id,
        private readonly int $per_page = 5
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(fn($user) => new User(
            id: $user['node']['id'],
            email: $user['node']['email'],
            gid: $user['node']['gid'],
            first_name: $user['node']['firstName'],
            last_name: $user['node']['lastName'],
            has_admin_role: $user['node']['hasAdminRole'] ?? null,
            custom_profile_fields: $user['node']['customProfileFields'] ?? null,
        ), $response->json('data.group.users.edges'));
    }

    protected function defaultBody(): array
    {

        return [
            'query' => '
        query UsersInGroup($groupId: ID!, $after: String, $first: Int) {
  group(id: $groupId) {
    createdAt
    id
    name
    users(first: $first, after: $after)  {
    pageInfo {
            endCursor
            hasNextPage
            hasPreviousPage
            startCursor
          }
      edges {
        node {
        id
    gid
    email
    firstName
     lastName
          
        }
      }
    }
  }
}',
            'variables' => [
                'groupId' => $this->group_id,
                'first' => $this->per_page,
                'after' => $this->after
            ]
        ];

    }


    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator
        {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.group.users.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json('data.group.users.pageInfo.hasNextPage'));
            }

            protected function getPageItems(Response $response, Request $request): array
            {
                return $response->dto();
            }

            protected function applyPagination(Users|Request $request): Request
            {
                if(is_null($this->currentResponse)) {
                    return $request;
                }

                $request->after = $this->getNextCursor($this->currentResponse);

                return $request;
            }
        };
    }
}