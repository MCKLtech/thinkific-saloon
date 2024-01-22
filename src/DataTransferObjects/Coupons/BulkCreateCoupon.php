<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Coupons;

final class BulkCreateCoupon
{
    public function __construct(
        public int $promotion_id,
        public int $bulk_quantity_per_coupon,
        public int $bulk_coupon_code_length,
        public int $bulk_quantity,
    ) {

    }

}