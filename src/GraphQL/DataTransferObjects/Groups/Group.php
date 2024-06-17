<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Groups;

use Carbon\Carbon;

final class Group
{
    public function __construct(
        public Carbon $created_at,
        public int    $id,
        public string $name,

    )
    {
    }

}