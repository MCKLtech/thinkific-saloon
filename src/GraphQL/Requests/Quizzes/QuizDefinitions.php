<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes;

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
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizDefinition;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizDefinitionChoice;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizDefinitionQuestion;

final class QuizDefinitions extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    /** Outer quizzes cursor. */
    public ?string $after = null;

    /** Cursor into the nested questions connection (applied to EVERY quiz on the page). */
    public ?string $questionsAfter = null;

    public function __construct(
        private readonly array $filter = [],
        private readonly int $per_page = 25,
        private readonly int $questions_per_page = 25,
        private readonly int $choices_per_page = 15,
        ?string $questions_after = null,
    ) {
        $this->questionsAfter = $questions_after;
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    /**
     * @return QuizDefinition[]
     */
    public function createDtoFromResponse(Response $response): array
    {
        $nodes = $response->json('data.site.quizzes.nodes') ?? [];

        return array_map(function (array $quiz): QuizDefinition {
            $questions = $quiz['questions']['nodes'] ?? [];

            return new QuizDefinition(
                id: (string) $quiz['id'],
                name: isset($quiz['name']) ? trim(strip_tags($quiz['name'])) : null,
                questions: array_map(function (array $question): QuizDefinitionQuestion {
                    $choices = $question['choices']['nodes'] ?? [];

                    return new QuizDefinitionQuestion(
                        id: (string) $question['id'],
                        prompt: isset($question['prompt']) ? trim(strip_tags($question['prompt'])) : null,
                        position: $question['position'] ?? null,
                        type: $question['type'] ?? null,
                        choices: array_map(fn (array $choice): QuizDefinitionChoice => new QuizDefinitionChoice(
                            id: (string) $choice['id'],
                            text: isset($choice['text']) ? trim(strip_tags($choice['text'])) : null,
                            position: $choice['position'] ?? null,
                            correct: $choice['correct'] ?? false,
                        ), $choices),
                        hasMoreChoices: (bool) ($question['choices']['pageInfo']['hasNextPage'] ?? false),
                        choicesEndCursor: $question['choices']['pageInfo']['endCursor'] ?? null,
                    );
                }, $questions),
                hasMoreQuestions: (bool) ($quiz['questions']['pageInfo']['hasNextPage'] ?? false),
                questionsEndCursor: $quiz['questions']['pageInfo']['endCursor'] ?? null,
            );
        }, $nodes);
    }

    protected function defaultBody(): array
    {
        $filter = array_filter($this->filter, fn ($v) => ! is_null($v));

        return [
            'query' => 'query SiteQuizDefinitions($first: Int, $after: String, $filter: QuizFilter, $questionsFirst: Int, $questionsAfter: String, $choicesFirst: Int) {
  site {
    quizzes(first: $first, after: $after, filter: $filter) {
      pageInfo {
        endCursor
        hasNextPage
        hasPreviousPage
        startCursor
      }
      nodes {
        id
        name
        questions(first: $questionsFirst, after: $questionsAfter) {
          pageInfo {
            endCursor
            hasNextPage
          }
          nodes {
            id
            prompt
            position
            type
            choices(first: $choicesFirst) {
              pageInfo {
                endCursor
                hasNextPage
              }
              nodes {
                id
                text
                position
                correct
              }
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
                'filter' => empty($filter) ? null : $filter,
                'questionsFirst' => $this->questions_per_page,
                'questionsAfter' => $this->questionsAfter,
                'choicesFirst' => $this->choices_per_page,
            ],
        ];
    }

    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.site.quizzes.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json('data.site.quizzes.pageInfo.hasNextPage'));
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
