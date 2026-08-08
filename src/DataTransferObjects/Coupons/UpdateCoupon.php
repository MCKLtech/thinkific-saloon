<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Coupons;

final class UpdateCoupon
{
    public function __construct(
        public int $coupon_id,
        /**
         * Required by Thinkific's UpdateCoupon schema, unlike every other
         * field here - omitting it produces a 422 from the real API rather
         * than a client-side error, so it's non-nullable here to fail fast
         * instead.
         */
        public string $code,
        public ?string $note,
        public ?int $quantity,
        public ?int $quantity_used
    ) {

    }

}