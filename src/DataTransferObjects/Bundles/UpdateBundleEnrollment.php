<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Bundles;

use Carbon\Carbon;

final class UpdateBundleEnrollment
{
    public function __construct(
        public int $productable_id,
        public int $user_id,
        public ?Carbon $activated_at,
        public ?Carbon $expiry_date,
    ) {}

}