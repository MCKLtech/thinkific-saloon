<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Promotions;

use Carbon\Carbon;

final class Promotion
{
    public function __construct(
        public int    $id,
        public string $name,
        public ?string $description,
        public ?Carbon $starts_at,
        public ?Carbon $expires_at,
        public string $discount_type,
        public int    $amount,
        public ?array  $coupon_ids,
        public ?array  $product_ids,
        public ?int    $duration
    )
    {

    }
}