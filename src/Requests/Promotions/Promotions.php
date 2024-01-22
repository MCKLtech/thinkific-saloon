<?php

namespace WooNinja\ThinkificSaloon\Requests\Promotions;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\Promotion;

final class Promotions extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return "promotions";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $promotion) {
            return new Promotion(
                id: $promotion['id'],
                name: $promotion['name'],
                description: $promotion['description'],
                starts_at: Carbon::parse($promotion['starts_at']),
                expires_at: Carbon::parse($promotion['expires_at']),
                discount_type: $promotion['discount_type'],
                amount: $promotion['amount'],
                coupon_ids: $promotion['coupon_ids'],
                product_ids: $promotion['product_ids'] ?? [],
                duration: $promotion['duration']
            );
        }, $response->json('items'));
    }
}