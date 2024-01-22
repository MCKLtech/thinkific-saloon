<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Coupons;

final class CreateCoupon
{
    public function __construct(
        public int $promotion_id,
        public string $code,
        public ?int $quantity,
        public ?string $note
    ) {

    }

}