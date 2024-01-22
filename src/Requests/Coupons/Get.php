<?php

namespace WooNinja\ThinkificSaloon\Requests\Coupons;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\Coupon;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $coupon_id
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "coupons/{$this->coupon_id}";
    }

    public function createDtoFromResponse(Response $response): Coupon
    {
        $coupon = $response->json();

        return new Coupon(
            id: $coupon['id'],
            code: $coupon['code'],
            note: $coupon['note'],
            quantity_used: $coupon['quantity_used'],
            quantity: $coupon['quantity'],
            promotion_id: $coupon['promotion_id'],
            created_at: Carbon::parse($coupon['created_at'])
        );
    }


}