<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses;

final class Chapter
{
    public function __construct(
        public int    $id,
        public int $position,
        public string $title,
        /** @var Lesson[] $lessons */
        public array $lessons,
    )
    {
    }

}