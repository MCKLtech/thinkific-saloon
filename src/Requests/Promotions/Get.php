<?php

namespace WooNinja\ThinkificSaloon\Requests\Promotions;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\Promotion;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $promotion_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "promotions/{$this->promotion_id}";
    }

    public function createDtoFromResponse(Response $response): Promotion
    {
        $responseData = $response->json();

        return new Promotion(
            id: $responseData['id'],
            name: $responseData['name'],
            description: $responseData['description'],
            starts_at: Carbon::parse($responseData['starts_at']),
            expires_at: $responseData['expires_at'] ? Carbon::parse($responseData['expires_at']) : null,
            discount_type: $responseData['discount_type'],
            amount: $responseData['amount'],
            coupon_ids: $responseData['coupon_ids'],
            product_ids: $responseData['product_ids'] ?? [],
            duration: $responseData['duration']
        );
    }
}