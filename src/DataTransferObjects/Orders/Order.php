<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Orders;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Orders\OrderInterface;

class Order implements OrderInterface
{
    public function __construct(
        public int     $id,
        public Carbon  $created_at,
        public int     $user_id,
        public string  $user_email,
        public string  $user_name,
        public string  $product_name,
        public int     $product_id,
        public float   $amount_dollars,
        public int     $amount_cents,
        public bool    $subscription,
        public ?string $coupon_code,
        public ?int    $coupon_id,
        public ?string $affiliate_referral_code,
        public string  $status,
        public array   $items
    )
    {
    }

}