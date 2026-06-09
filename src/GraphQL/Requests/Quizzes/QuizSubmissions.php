<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes;

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
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\Quiz;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizAnswer;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizChoice;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizQuestion;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizSubmission;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class QuizSubmissions extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public ?string $after = null;

    public function __construct(
        private readonly array $filter = [],
        private readonly int   $per_page = 25,
        private readonly int   $answers_per_page = 25,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(fn($submission) => new QuizSubmission(
            id: $submission['id'],
            attempts: $submission['attempts'],
            correctCount: $submission['correctCount'],
            incorrectCount: $submission['incorrectCount'],
            passed: $submission['passed'],
            completedAt: isset($submission['completedAt']) ? Carbon::parse($submission['completedAt']) : null,
            createdAt: Carbon::parse($submission['createdAt']),
            quiz: new Quiz(
                id: $submission['quiz']['id'],
                name: $submission['quiz']['name'] ?? null,
                passingScore: $submission['quiz']['passingScore'] ?? null,
            ),
            user: new User(
                id: (int)$submission['user']['id'],
                email: $submission['user']['email'],
                first_name: $submission['user']['firstName'] ?? null,
                last_name: $submission['user']['lastName'] ?? null,
            ),
            userAnswers: array_map(fn($answer) => new QuizAnswer(
                question: new QuizQuestion(
                    id: $answer['question']['id'],
                    prompt: $answer['question']['prompt'] ?? null,
                ),
                choices: array_map(fn($choice) => new QuizChoice(
                    id: $choice['id'],
                    text: $choice['text'] ?? null,
                    position: $choice['position'] ?? null,
                    correct: $choice['correct'] ?? false,
                ), $answer['choices']),
            ), $submission['userAnswers']['nodes']),
        ), $response->json('data.site.quizSubmissions.nodes'));
    }

    protected function defaultBody(): array
    {
        $filter = array_filter($this->filter, fn($v) => !is_null($v));

        return [
            'query' => 'query SiteQuizSubmissions($first: Int, $after: String, $filter: QuizSubmissionFilter, $answersFirst: Int) {
  site {
    quizSubmissions(first: $first, after: $after, filter: $filter) {
      pageInfo {
        endCursor
        hasNextPage
        hasPreviousPage
        startCursor
      }
      nodes {
        id
        attempts
        correctCount
        incorrectCount
        passed
        completedAt
        createdAt
        quiz {
          id
          name
          passingScore
        }
        user {
          id
          email
          firstName
          lastName
        }
        userAnswers(first: $answersFirst) {
          nodes {
            question {
              id
              prompt
            }
            choices {
              id
              text
              correct
              position
            }
          }
        }
      }
    }
  }
}',
            'variables' => [
                'first'       => $this->per_page,
                'after'       => $this->after,
                'filter'      => empty($filter) ? null : $filter,
                'answersFirst' => $this->answers_per_page,
            ],
        ];
    }

    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.site.quizSubmissions.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json('data.site.quizSubmissions.pageInfo.hasNextPage'));
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
