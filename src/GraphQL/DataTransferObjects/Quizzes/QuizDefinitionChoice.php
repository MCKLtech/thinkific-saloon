<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes;

final class QuizDefinitionChoice
{
    public function __construct(
        public string  $id,
        public ?string $text = null,
        public ?int    $position = null,
        public bool    $correct = false,
    )
    {
    }
}
