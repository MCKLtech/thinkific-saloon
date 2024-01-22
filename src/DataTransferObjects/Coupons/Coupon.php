<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Coupons;

use Carbon\Carbon;

final class Coupon
{
    public function __construct(
        public int $id,
        public string $code,
        public ?string $note,
        public int $quantity_used,
        public int $quantity,
        public int $promotion_id,
        public Carbon $created_at,

    ) {

    }

}