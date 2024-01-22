<?php

namespace WooNinja\ThinkificSaloon\Requests\Products;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Products\Product;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $product_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "products/{$this->product_id}";
    }

    public function createDtoFromResponse(Response $response): Product
    {
        $responseData = $response->json();

        return new Product(
            id: $responseData['id'],
            created_at: Carbon::parse($responseData['created_at']),
            productable_id: $responseData['productable_id'],
            productable_type: $responseData['productable_type'],
            price: $responseData['price'],
            position: $responseData['position'],
            status: $responseData['status'],
            name: $responseData['name'],
            private: $responseData['private'],
            hidden: $responseData['hidden'],
            subscription: $responseData['subscription'],
            days_until_expiry: $responseData['days_until_expiry'],
            has_certificate: $responseData['has_certificate'],
            keywords: $responseData['keywords'],
            seo_title: $responseData['seo_title'],
            seo_description: $responseData['seo_description'],
            collection_ids: $responseData['collection_ids'],
            related_product_ids: $responseData['related_product_ids'],
            description: $responseData['description'],
            card_image_url: $responseData['card_image_url'],
            slug: $responseData['slug'],
            product_prices: $responseData['product_prices']
        );
    }
}