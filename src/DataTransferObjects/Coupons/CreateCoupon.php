<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Coupons;

use WooNinja\LMSContracts\Contracts\DTOs\Coupons\CreateCouponInterface;

final class CreateCoupon implements CreateCouponInterface
{
    public function __construct(
        public int $promotion_id,
        public string $code,
        public ?int $quantity,
        public ?string $note
    ) {

    }

}