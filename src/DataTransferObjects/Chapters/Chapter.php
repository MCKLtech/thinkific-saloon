<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Chapters;

final class Chapter
{
    public function __construct(
        public int $id,
        public string $name,
        public int $position,
        public ?string $description,
        public ?int $duration_in_seconds,
        public array $content_ids,
    )
    {

    }
}