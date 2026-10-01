<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes;

final class QuizDefinitionQuestion
{
    public function __construct(
        public string  $id,
        public ?string $prompt = null,
        public ?int    $position = null,
        public ?string $type = null,
        /** @var QuizDefinitionChoice[] */
        public array   $choices = [],
        public bool    $hasMoreChoices = false,
        public ?string $choicesEndCursor = null,
    )
    {
    }
}
