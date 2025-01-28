<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys;

use Carbon\Carbon;

final class Survey
{
    public function __construct(
        public int    $id,
        public Carbon $created_at,
        /** @var Question[] $questions */
        public array  $questions,
    )
    {
    }

}