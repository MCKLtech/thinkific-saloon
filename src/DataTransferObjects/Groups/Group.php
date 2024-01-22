<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Groups;

use Carbon\Carbon;

final class Group
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $token,
        public Carbon $created_at
    ) {

    }
}