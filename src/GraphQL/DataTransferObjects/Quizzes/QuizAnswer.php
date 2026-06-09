<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes;

final class QuizAnswer
{
    public function __construct(
        public QuizQuestion $question,
        /** @var QuizChoice[] */
        public array        $choices,
    )
    {
    }
}
