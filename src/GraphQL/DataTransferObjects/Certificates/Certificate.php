<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Certificates;

use Carbon\Carbon;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Products\Product;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final readonly class Certificate
{
    public function __construct(
        public string $id,
        public string $credential_id,
        public string $pdf_download_path,
        public Carbon $issued_at,
        public ?Carbon $expiry_date,
        public ?User $user = null,
        public ?Course $course = null,
        public ?Product $product = null,
    )
    {
    }
}