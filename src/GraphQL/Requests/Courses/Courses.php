<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Courses;

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
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Course;

final class Courses extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    public ?string $after = null;

    use HasJsonBody;

    public function __construct(
        private readonly int $per_page = 100
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(fn($course) => new Course(
            id: $course['id'],
            title: $course['title'],
            name: $course['name'],
            slug: $course['slug']
        ), $response->json('data.site.courses.nodes'));
    }

    protected function defaultBody(): array
    {

        return [
            'query' => '
        query ThinkificSiteCourses($first: Int, $after: String) {
  site {
    courses(first: $first, after: $after) {
      nodes {
        name
        id
        title
        slug
      }
      pageInfo {
        startCursor
        hasPreviousPage
        hasNextPage
        endCursor
      }
    }
  }
}
',
            'variables' => [
                'first' => $this->per_page,
                'after' => $this->after
            ]
        ];

    }


    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.site.courses.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json('data.site.courses.pageInfo.hasNextPage'));
            }

            protected function getPageItems(Response $response, Request $request): array
            {
                return $response->dto();
            }

            protected function applyPagination(Request $request): Request
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