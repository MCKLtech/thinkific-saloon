<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Products;

final readonly class Product
{
    public function __construct(
        public string $id,
        public string $productable_id,
        public string $status,
        public string $slug,
        public string $name,
    )
    {
    }
}