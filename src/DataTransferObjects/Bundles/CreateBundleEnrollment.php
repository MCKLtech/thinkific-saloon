<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Bundles;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Bundles\CreateBundleEnrollmentInterface;

final class CreateBundleEnrollment implements CreateBundleEnrollmentInterface
{

    public function __construct(
        public int $productable_id,
        public int $user_id,
        public ?Carbon $activated_at,
        public ?Carbon $expiry_date,
    ) {}

}