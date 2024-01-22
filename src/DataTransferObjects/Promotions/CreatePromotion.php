<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Promotions;

use Carbon\Carbon;

final class CreatePromotion
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?Carbon $starts_at,
        public ?Carbon $expires_at,
        public string $discount_type,
        public int $amount,
        public ?array $product_ids,
        public ?array $coupon_ids,
        public ?int $duration
    )
    {

    }

}