<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Surveys\Surveys;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Surveys\UserSurveys;

class SurveyService extends Resource
{
    /**
     * Return a list of Surveys
     *
     * Filter keys: completedAt (DateTimeFilter), courseName
     *
     * @param int   $per_page
     * @param int   $questions_per_page
     * @param int   $choices_per_page
     * @param array $filter
     * @return Paginator
     */
    public function surveys(int $per_page = 25, int $questions_per_page = 50, int $choices_per_page = 25, array $filter = []): Paginator
    {
        $surveys = new Surveys($per_page, $questions_per_page, $choices_per_page, $filter);

        return $surveys->paginate($this->connector);

    }

    /**
     * Return a list of Surveys for a given Thinkific User ID
     *
     * Each response carries at most $user_answers answers. When a response has
     * more, its DTO reports hasMoreAnswers = true and answersEndCursor; re-issue
     * this call with that cursor as $answers_after and pick the matching
     * SurveyResponse::$id out of the result to get the next page of answers.
     * Note $answers_after is applied to every response in the page, so only the
     * answers of the response the cursor came from are meaningful.
     *
     * Cost: as with QuizService::submissions(), cost scales with
     * $per_page x $user_answers and a single request may not exceed 1000 points;
     * over that Thinkific returns MAX_QUERY_COST_EXCEEDED (thrown as
     * MaxQueryCostExceededException). Keep $user_answers near the survey's real
     * question count and lean on hasMoreAnswers / $answers_after for overflow.
     *
     * @param int         $user_id
     * @param int         $per_page
     * @param int         $user_answers
     * @param string|null $answers_after  userAnswers cursor from a prior SurveyResponse::$answersEndCursor
     * @return Paginator
     */
    public function surveysForUser(int $user_id, int $per_page = 10, int $user_answers = 25, ?string $answers_after = null): Paginator
    {
        $userResponses = new UserSurveys($user_id, $per_page, $user_answers, $answers_after);

        return $userResponses->paginate($this->connector);

    }


}