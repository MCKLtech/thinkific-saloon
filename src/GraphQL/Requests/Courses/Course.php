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
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Chapter;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Content;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Lesson;

final class Course extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    public ?string $after = null;

    use HasJsonBody;

    public function __construct(
        private readonly int $course_id,
        private readonly int $chapters_per_page,
        private readonly int $lessons_per_page,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(fn($chapter) => new Chapter(
            id: $chapter['id'],
            position: $chapter['position'],
            title: $chapter['title'],
            lessons: array_map(fn($lesson) => new Lesson(
                id: $lesson['id'],
                lessonType: $lesson['lessonType'],
                title: $lesson['title'],
                takeUrl: $lesson['takeUrl'],
                content: new Content(
                    id: $lesson['content']['id'],
                    contentType: $lesson['content']['contentType']
                )
            ), $chapter['lessons']['nodes'])
        ), $response->json('data.course.curriculum.chapters.nodes'));
    }

    protected function defaultBody(): array
    {

        return [
            'query' => '
        query ThinkificCourseGet($courseId: ID!, $first: Int, $after: String, $lessonsFirst: Int, $lessonsAfter: String) {
  course(id: $courseId) {
    curriculum {
      chapters(first: $first, after: $after) {
        nodes {
          id
          position
          title
          lessons(first: $lessonsFirst, after: $lessonsAfter) {
            nodes {
              id
              lessonType
              title
              takeUrl
              content {
                contentType
                id
              }
            }
          }
        }
        pageInfo {
          endCursor
          hasNextPage
          hasPreviousPage
          startCursor
        }
      }
    }
  }
}
',
            'variables' => [
                'courseId' => $this->course_id,
                'first' => $this->chapters_per_page,
                'after' => $this->after,
                'lessonsFirst' => $this->lessons_per_page,
                'lessonsAfter' => null
            ]
        ];

    }


    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.course.curriculum.chapters.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json('data.course.curriculum.chapters.hasNextPage'));
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