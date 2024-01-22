<?php

namespace WooNinja\ThinkificSaloon\Requests\Coupons;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\Coupon;

final class Coupons extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $promotion_id
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "coupons";
    }

    protected function defaultQuery(): array
    {
        return [
            'promotion_id' => $this->promotion_id
        ];
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $coupon) {
            return new Coupon(
                id: $coupon['id'],
                code: $coupon['code'],
                note: $coupon['note'],
                quantity_used: $coupon['quantity_used'],
                quantity: $coupon['quantity'],
                promotion_id: $coupon['promotion_id'],
                created_at: Carbon::parse($coupon['created_at'])
            );
        }, $response->json('items'));
    }

}