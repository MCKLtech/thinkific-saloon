<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Responses;

use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Choice;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Question;

final class UserAnswer
{
    public function __construct(
        public string|null $textResponse,
        public Question    $question,
        public bool        $skipped,
        /** @var Choice[] $choices */
        public array       $choices,
    )
    {
    }

}