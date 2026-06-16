<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes;

/**
 * Describes where a Quiz lives within the curriculum: the Course it belongs to,
 * the Chapter, and the Lesson (Content) that hosts it.
 *
 * Built by QuizService::quizLocations() since the GraphQL schema only exposes
 * the Content -> Quiz relationship, not the reverse.
 */
final class QuizLocation
{
    public function __construct(
        public string  $quizId,
        public int     $courseId,
        public ?string $courseName,
        public ?string $courseSlug,
        public int     $chapterId,
        public string  $chapterTitle,
        public int     $lessonId,
        public string  $lessonTitle,
        public int     $contentId,
    )
    {
    }
}