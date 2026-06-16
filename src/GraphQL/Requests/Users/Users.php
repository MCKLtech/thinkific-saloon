<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Users;

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
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\Avatar;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class Users extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    public ?string $after = null;

    use HasJsonBody;

    public function __construct(
        private readonly int $per_page = 100,
        private readonly int $custom_fields_per_page = 50,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        $edges = $response->json('data.site.users.edges') ?? [];

        return array_map(fn($user) => new User(
            id: $user['node']['id'],
            email: $user['node']['email'],
            gid: $user['node']['gid'],
            first_name: $user['node']['firstName'],
            last_name: $user['node']['lastName'],
            has_admin_role: $user['node']['hasAdminRole'] ?? null,
            custom_profile_fields: $user['node']['customProfileFields'] ?? null,
            avatar: isset($user['node']['profile']['avatar'])
                ? new Avatar(
                    url: $user['node']['profile']['avatar']['url'],
                    alt_text: $user['node']['profile']['avatar']['altText'] ?? null,
                )
                : null,
        ), $edges);
    }

    protected function defaultBody(): array
    {

        return [
            'query' => 'query SiteUsers($first: Int, $after: String, $customFieldsFirst: Int) {
  site {
    users(first: $first, after: $after) {
      pageInfo {
            endCursor
            hasNextPage
            hasPreviousPage
            startCursor
          }
      edges {
        node{
          id
    gid
    email
    firstName
     lastName
    hasAdminRole
    customProfileFields(first: $customFieldsFirst) {
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
      }
    }
  }',
            'variables' => [
                'first' => $this->per_page,
                'after' => $this->after,
                'customFieldsFirst' => $this->custom_fields_per_page
            ]
        ];
    }


    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator
        {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.site.users.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json('data.site.users.pageInfo.hasNextPage'));
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