<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes;

final class Quiz
{
    public function __construct(
        public string $id,
        public string $name,
        public ?int   $passingScore = null,
    )
    {
    }
}
