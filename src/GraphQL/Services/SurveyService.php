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
     * @param int $per_page
     * @return Paginator
     */
    public function surveys(int $per_page = 25): Paginator
    {
        $surveys = new Surveys($per_page);

        return $surveys->paginate($this->connector);

    }

    /**
     * Return a list of Surveys for a given Thinkific User ID
     *
     * @param int $user_id
     * @param int $per_page
     * @param int $user_answers
     * @return Paginator
     */
    public function surveysForUser(int $user_id, int $per_page = 10, int $user_answers = 25): Paginator
    {
        $userResponses = new UserSurveys($user_id, $per_page, $user_answers);

        return $userResponses->paginate($this->connector);

    }


}