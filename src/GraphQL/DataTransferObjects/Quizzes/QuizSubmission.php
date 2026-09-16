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
        /**
         * True when the API has more answers for this submission than fit in
         * one page. Re-query with `answers_after: $answersEndCursor` to fetch
         * the next page of answers.
         */
        public bool    $hasMoreAnswers = false,
        public ?string $answersEndCursor = null,
    )
    {
    }

    public function percentageScore(): float
    {
        $total = $this->correctCount + $this->incorrectCount;

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->correctCount / $total) * 100, 2);
    }
}
