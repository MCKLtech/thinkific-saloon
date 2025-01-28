<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Surveys;

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
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Choice;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Question;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Responses\SurveyResponse;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Responses\UserAnswer;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class UserSurveys extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public ?string $after = null;

    public function __construct(
        private readonly int $user_id,
        private readonly int $per_page = 10,
        private readonly int $user_answers = 25
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(fn($survey) => new SurveyResponse(
            id: $survey['id'],
            created_at: Carbon::parse($survey['createdAt']),
            completed_at: Carbon::parse($survey['completedAt']),
            user: new User(
                id: $survey['user']['id'],
                gid: $survey['user']['gid'] ?? null,
                first_name: $survey['user']['firstName'] ?? null,
                last_name: $survey['user']['lastName'] ?? null,
                email: $survey['user']['email']
            ),
            userAnswers: array_map(fn($userAnswer) => new UserAnswer(
                textResponse: $userAnswer['textResponse'],
                question: new Question(
                    id: $userAnswer['question']['id']
                ),
                skipped: $userAnswer['skipped'],
                choices: array_map(fn($choice) => new Choice(
                    id: $choice['id'],
                    text: $choice['text']
                ), $userAnswer['choices'])
            ), $survey['userAnswers']['nodes'])
        ), $response->json('data.site.surveySubmissions.nodes'));
    }

    protected function defaultBody(): array
    {
        return [
            'query' => 'query UserSurveySubmissions($first: Int, $after: String, $filter: SurveySubmissionsFilter, $userAnswersFirst2: Int) {
  site {
    surveySubmissions(first: $first, after: $after, filter: $filter) {
      pageInfo {
        endCursor
        hasNextPage
        hasPreviousPage
        startCursor
      }
      nodes {
        completedAt
        createdAt
        id
        user {
          email
          id
        }
        userAnswers(first: $userAnswersFirst2) {
          nodes {
            textResponse
            question {
              id
            }
            skipped
            choices {
              text
              id
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
                'filter' => [
                    'userIds' => $this->user_id
                ],
                'userAnswersFirst2' => $this->user_answers
            ]
        ];
    }

    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.site.surveySubmissions.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json('data.site.surveySubmissions.pageInfo.hasNextPage'));
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