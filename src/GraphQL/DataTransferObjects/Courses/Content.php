<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses;

final class Content
{
    public function __construct(
        public int     $id,
        public string  $contentType,
        /** The id of the Quiz this Content represents, when contentType is QUIZ. Null otherwise. */
        public ?string $quizId = null,
    )
    {
    }

}