<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Quizzes;

use Carbon\Carbon;
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
        public Quiz $quiz,
        public Carbon $created_at,
        public Carbon $updated_at,
    )
    {
    }
}