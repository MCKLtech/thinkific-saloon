<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Responses;

use Carbon\Carbon;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class SurveyResponse
{
    public function __construct(
        public int    $id,
        public Carbon $created_at,
        public Carbon $completed_at,
        public ?User  $user = null,
        /** @var UserAnswer[] $choices */
        public array $userAnswers,
    )
    {
    }

}