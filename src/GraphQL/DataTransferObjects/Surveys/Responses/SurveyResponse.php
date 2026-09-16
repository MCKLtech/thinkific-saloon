<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Responses;

use Carbon\Carbon;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class SurveyResponse
{
    public function __construct(
        public int    $id,
        public Carbon $created_at,
        public ?Carbon $completed_at,
        /** @var UserAnswer[] $choices */
        public array  $userAnswers,
        public int    $survey_id,
        public ?User  $user = null,
        /**
         * True when the API has more answers for this submission than fit in
         * one page. Re-query with `answers_after: $answersEndCursor` to fetch
         * the next page of answers.
         */
        public bool    $hasMoreAnswers = false,
        public ?string $answersEndCursor = null,
    )
    {
    }

}