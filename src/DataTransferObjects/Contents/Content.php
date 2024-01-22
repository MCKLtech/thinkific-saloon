<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Contents;

final class Content
{
    public function __construct(
        public int    $id,
        public string $name,
        public int    $position,
        public int    $chapter_id,
        public string $contentable_type,
        public bool   $free,
        public string $take_url,
    )
    {

    }

}