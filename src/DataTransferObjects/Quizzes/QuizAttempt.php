<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Quizzes;

use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;

final class QuizAttempt
{
    public function __construct(
        public int  $attempts,
        public int  $correct_count,
        public int  $grade,
        public int  $incorrect_count,
        public int  $result_id,
        public User $user,
        public Quiz $quiz
    )
    {
    }
}