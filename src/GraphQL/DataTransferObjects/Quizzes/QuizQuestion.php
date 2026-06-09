<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes;

final class QuizQuestion
{
    public function __construct(
        public string  $id,
        public ?string $prompt = null,
        public ?int    $position = null,
        public ?string $type = null,
    )
    {
    }
}
