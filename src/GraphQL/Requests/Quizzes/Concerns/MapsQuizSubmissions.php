<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\Concerns;

use Carbon\Carbon;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\CursorPaginator;
use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\Quiz;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizAnswer;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizChoice;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizQuestion;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizSubmission;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

/**
 * Shared outer-connection machinery for the site.quizSubmissions queries.
 *
 * Both QuizSubmissions (which selects a userAnswers block) and
 * QuizSubmissionsSummary (which omits it) read the same
 * data.site.quizSubmissions connection, so the DTO mapping and the
 * CursorPaginator live here. The mapping tolerates a missing userAnswers
 * connection, which is what makes it safe for the no-answers summary shape.
 */
trait MapsQuizSubmissions
{
    public ?string $after = null;

    public function createDtoFromResponse(Response $response): array
    {
        $nodes = $response->json('data.site.quizSubmissions.nodes') ?? [];

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
                    prompt: isset($answer['question']['prompt']) ? trim(strip_tags($answer['question']['prompt'])) : null,
                    position: $answer['question']['position'] ?? null,
                    type: $answer['question']['type'] ?? null,
                ),
                choices: array_map(fn($choice) => new QuizChoice(
                    id: $choice['id'],
                    text: isset($choice['text']) ? trim(strip_tags($choice['text'])) : null,
                    position: $choice['position'] ?? null,
                    correct: $choice['correct'] ?? false,
                ), $answer['choices'] ?? []),
            ), $submission['userAnswers']['nodes'] ?? []),
            hasMoreAnswers: (bool)($submission['userAnswers']['pageInfo']['hasNextPage'] ?? false),
            answersEndCursor: $submission['userAnswers']['pageInfo']['endCursor'] ?? null,
        ), $nodes);
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
