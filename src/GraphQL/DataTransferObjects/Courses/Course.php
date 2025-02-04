<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses;

final class Course
{
    public function __construct(
        public int    $id,
        public string $title,
        public string $name,
        public string $slug
    )
    {
    }

}