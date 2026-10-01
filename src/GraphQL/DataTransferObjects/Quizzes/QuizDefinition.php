<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes;

final class QuizDefinition
{
    public function __construct(
        public string  $id,
        public ?string $name = null,
        /** @var QuizDefinitionQuestion[] */
        public array   $questions = [],
        public bool    $hasMoreQuestions = false,
        public ?string $questionsEndCursor = null,
    )
    {
    }
}
