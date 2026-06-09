<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\QuizSubmissions;

class QuizService extends Resource
{
    /**
     * Return a paginated list of quiz submissions.
     *
     * Filter keys: userIds, courseIds, groupIds
     *
     * @param array $filter
     * @param int   $per_page
     * @param int   $answers_per_page
     * @return Paginator
     */
    public function submissions(array $filter = [], int $per_page = 10, int $answers_per_page = 10): Paginator
    {
        return (new QuizSubmissions($filter, $per_page, $answers_per_page))
            ->paginate($this->connector);
    }

    /**
     * Return quiz submissions for a specific user.
     *
     * @param int $userId
     * @param int $per_page
     * @param int $answers_per_page
     * @return Paginator
     */
    public function submissionsForUser(int $userId, int $per_page = 10, int $answers_per_page = 10): Paginator
    {
        return $this->submissions(['userIds' => [$userId]], $per_page, $answers_per_page);
    }
}
