<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes;

use Carbon\Carbon;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class QuizSubmission
{
    public function __construct(
        public string  $id,
        public int     $attempts,
        public int     $correctCount,
        public int     $incorrectCount,
        public bool    $passed,
        public ?Carbon $completedAt,
        public Carbon  $createdAt,
        public Quiz    $quiz,
        public User    $user,
        /** @var QuizAnswer[] */
        public array   $userAnswers,
    )
    {
    }
}
