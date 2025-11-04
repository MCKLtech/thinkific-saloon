<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Coupons;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Coupons\CouponInterface;

final class Coupon implements CouponInterface
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