<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys;

final class Choice
{
    public function __construct(
        public int    $id,
        public ?string $text = '',
        public ?int    $position = null

    )
    {
    }

}