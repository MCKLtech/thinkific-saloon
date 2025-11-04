<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Products;

use Carbon\Carbon;
use WooNinja\LMSContracts\Contracts\DTOs\Products\ProductInterface;

class Product implements ProductInterface
{
    public function __construct(
        public int     $id,
        public Carbon  $created_at,
        public int     $productable_id,
        public string  $productable_type,
        public float   $price,
        public int     $position,
        public string  $status,
        public string  $name,
        public bool    $private,
        public bool    $hidden,
        public bool    $subscription,
        public ?int    $days_until_expiry,
        public bool    $has_certificate,
        public ?string $keywords,
        public ?string $seo_title,
        public ?string $seo_description,
        public array   $collection_ids,
        public array   $related_product_ids,
        public ?string $description,
        public ?string $card_image_url,
        public string  $slug,
        public array   $product_prices,


    )
    {
    }
}