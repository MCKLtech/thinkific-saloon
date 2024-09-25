<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Quizzes;

final class Quiz
{
    public function __construct(
        public int    $id,
        public string $name,
    )
    {

    }

}
