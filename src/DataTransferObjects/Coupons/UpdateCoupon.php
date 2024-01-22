<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Coupons;

final class UpdateCoupon
{
    public function __construct(
        public int $coupon_id,
        public ?string $code,
        public ?string $note,
        public ?int $quantity,
        public ?int $quantity_used
    ) {

    }

}