<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses;

final class Lesson
{
    public function __construct(
        public int    $id,
        public string $lessonType,
        public string $title,
        public string $takeUrl,
    )
    {
    }

}