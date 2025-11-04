<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Groups;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Groups\GroupInterface;

final class Group implements GroupInterface
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $token,
        public Carbon $created_at
    ) {

    }
}