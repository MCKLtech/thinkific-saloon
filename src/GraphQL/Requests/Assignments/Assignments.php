<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Assignments;

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
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments\Submission;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Users;

final class Assignments extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public ?string $after = null;

    public function __construct(
        private readonly int  $per_page = 50,
        private readonly ?int $user_id = null,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(fn($submission) => new Submission(
            id: $submission['node']['id'],
            status: $submission['node']['status'],
            name: $submission['node']['file']['name'],
            type: $submission['node']['file']['type'],
            url: $submission['node']['file']['url'],
            lesson_id: $submission['node']['assignment']['lesson']['id'],
            lesson_name: $submission['node']['assignment']['lesson']['title'],
            chapter_id: $submission['node']['assignment']['lesson']['chapter']['id'],
            chapter_name: $submission['node']['assignment']['lesson']['chapter']['title'],
            product_id: $submission['node']['assignment']['lesson']['course']['product']['id'],
            product_name: $submission['node']['assignment']['lesson']['course']['product']['name'],
            reviewed_at: is_null($submission['node']['reviewedAt']) ? null : Carbon::parse($submission['node']['reviewedAt']),
            updated_at: Carbon::parse($submission['node']['updatedAt']),
            created_at: Carbon::parse($submission['node']['createdAt']),
            user: new User(
                id: $submission['node']['user']['id'],
                email: $submission['node']['user']['email'],
                gid: $submission['node']['user']['gid'],
                first_name: $submission['node']['user']['firstName'],
                last_name: $submission['node']['user']['lastName'],
                has_admin_role: $submission['node']['user']['hasAdminRole'] ?? null,
                custom_profile_fields: $submission['node']['user']['customProfileFields'] ?? null,
            )
        ), $response->json('data.site.assignmentSubmissions.edges'));
    }

    protected function defaultBody(): array
    {
        $variables = [
            'first' => $this->per_page,
            'after' => $this->after,
            'filter' => null
        ];

        if (is_numeric($this->user_id)) {
            $variables['filter'] = [
                'userIds' => (string)$this->user_id // Ensure it's a string as per the expected JSON output
            ];
        }

        return [
            'query' => 'query StudentsAssignmentSubmissions($first: Int, $after: String, $filter: AssignmentSubmissionFilter) {
  site {
    assignmentSubmissions(first: $first, after: $after, filter: $filter) {
      pageInfo {
        endCursor
        hasNextPage
        hasPreviousPage
        startCursor
      }
      edges {
        node {
          file {
            name
            type
            url
          }
          user {
            id
            gid
            email
            firstName
            lastName
          }
          id
          reviewedAt
          status
          updatedAt
          createdAt
          assignment {
            lesson {
              id
              title
              chapter {
                id
                title
              }
              course {
                product {
                  id
                  name
                }
              }
            }
          }
        }
      }
    }
  }
}',
            'variables' => $variables
        ];
    }

    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.site.assignmentSubmissions.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json('data.site.assignmentSubmissions.pageInfo.hasNextPage'));
            }


            protected function getPageItems(Response $response, Request $request): array
            {
                return $response->dto();
            }

            protected function applyPagination(Users|Request $request): Request
            {
                if (is_null($this->currentResponse)) {
                    return $request;
                }

                $request->after = $this->getNextCursor($this->currentResponse);

                return $request;
            }
        };
    }
}