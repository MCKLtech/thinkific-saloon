<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Chapters;

use WooNinja\LMSContracts\Contracts\DTOs\Chapters\ChapterInterface;

final class Chapter implements ChapterInterface
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