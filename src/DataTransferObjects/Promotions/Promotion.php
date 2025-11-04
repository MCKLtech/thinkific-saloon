<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Promotions;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Promotions\PromotionInterface;

final class Promotion implements PromotionInterface
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