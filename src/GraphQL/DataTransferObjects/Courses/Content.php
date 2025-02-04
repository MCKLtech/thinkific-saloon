<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses;

final class Content
{
    public function __construct(
        public int    $id,
        public string $contentType
    )
    {
    }

}