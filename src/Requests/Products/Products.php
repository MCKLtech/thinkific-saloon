<?php

namespace WooNinja\ThinkificSaloon\Requests\Products;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Products\Product;

final class Products extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly array $filters = [],
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "products";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $product) {
            return new Product(
                id: $product['id'],
                created_at: Carbon::parse($product['created_at']),
                productable_id: $product['productable_id'],
                productable_type: $product['productable_type'],
                price: $product['price'],
                position: $product['position'],
                status: $product['status'],
                name: $product['name'],
                private: $product['private'],
                hidden: $product['hidden'],
                subscription: $product['subscription'],
                days_until_expiry: $product['days_until_expiry'],
                has_certificate: $product['has_certificate'],
                keywords: $product['keywords'],
                seo_title: $product['seo_title'],
                seo_description: $product['seo_description'],
                collection_ids: $product['collection_ids'],
                related_product_ids: $product['related_product_ids'],
                description: $product['description'],
                card_image_url: $product['card_image_url'],
                slug: $product['slug'],
                product_prices: $product['product_prices']
            );
        }, $response->json('items'));
    }

    protected function defaultQuery(): array
    {
        return $this->filters;
    }
}