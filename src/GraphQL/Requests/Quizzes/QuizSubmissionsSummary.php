<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\PaginationPlugin\Contracts\HasRequestPagination;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\Concerns\MapsQuizSubmissions;

/**
 * The no-answers shape of site.quizSubmissions.
 *
 * Measured cost: ~22 points for 10 rows versus ~522 for the same page with the
 * userAnswers block. Use when only counts/attempts are needed and the
 * per-question answers can be skipped. Returned QuizSubmission::userAnswers is
 * always empty and hasMoreAnswers is always false.
 */
final class QuizSubmissionsSummary extends Request implements HasBody, HasRequestPagination, Paginatable
{
    use HasJsonBody;
    use MapsQuizSubmissions;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly array $filter = [],
        private readonly int   $per_page = 10,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    protected function defaultBody(): array
    {
        $filter = array_filter($this->filter, fn($v) => !is_null($v));

        return [
            'query' => 'query SiteQuizSubmissionsSummary($first: Int, $after: String, $filter: QuizSubmissionFilter) {
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
      }
    }
  }
}',
            'variables' => [
                'first'  => $this->per_page,
                'after'  => $this->after,
                'filter' => empty($filter) ? null : $filter,
            ],
        ];
    }
}
