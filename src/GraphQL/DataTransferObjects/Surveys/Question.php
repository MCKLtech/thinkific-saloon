<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys;

final class Question
{
    public function __construct(
        public int     $id,
        public ?string $questionType = null,
        public ?int    $position = null,
        public ?string $prompt = null,
        /** @var Choice[] $choices */
        public ?array  $choices = []
    )
    {
    }

}