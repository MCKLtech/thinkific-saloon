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
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Survey;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Users;

final class Surveys extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public ?string $after = null;

    public function __construct(
        private readonly int $per_page = 25
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(fn($survey) => new Survey(
            id: $survey['id'],
            created_at: Carbon::parse($survey['createdAt']),
            questions: array_map(fn($question) => new Question(
                id: $question['id'],
                questionType: $question['questionType'],
                position: $question['position'],
                prompt: $question['prompt'],
                choices: array_map(fn($choice) => new Choice(
                    id: $choice['id'],
                    text: $choice['text'],
                    position: $choice['position']
                ), $question['choices']['nodes'])
            ), $survey['questions']['nodes'])

        ), $response->json('data.site.surveys.nodes'));
    }

    protected function defaultBody(): array
    {
        return [
            'query' => 'query SiteSurveys($first: Int, $after: String, $questionsFirst2: Int, $choicesFirst2: Int) {
  site {
    surveys(first: $first, after: $after) {
     pageInfo {
        endCursor
        hasNextPage
        hasPreviousPage
        startCursor
      }
      nodes {
        createdAt
        id
        questions(first: $questionsFirst2) {
          nodes {
            questionType
            choices(first: $choicesFirst2) {
              nodes {
                text
                position
                id
              }
            }
            id
            prompt
            position
          }
        }
      }
    }
  }
}',
            'variables' => [
                'first' => $this->per_page,
                'after' => $this->after,
                'questionsFirst2' => $this->per_page,
                'choicesFirst2' => $this->per_page
            ]
        ];
    }

    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.site.surveys.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json('data.site.surveys.pageInfo.hasNextPage'));
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