<?php

namespace WooNinja\ThinkificSaloon\Requests\Coupons;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\BulkCreateCoupon;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\Coupon;

final class BulkCreate extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly BulkCreateCoupon $createCoupon
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "coupons/bulk_create";
    }

    protected function defaultBody(): array
    {
        $body = [
            'bulk_quantity_per_coupon' => $this->createCoupon->bulk_quantity_per_coupon,
            'bulk_coupon_code_length' => $this->createCoupon->bulk_coupon_code_length ?? null,
            'bulk_quantity' => $this->createCoupon->bulk_quantity ?? null,
        ];


        return array_filter($body, fn($value) => $value !== null);
    }

    protected function defaultQuery(): array
    {
        return [
            'promotion_id' => $this->createCoupon->promotion_id
        ];
    }

    public function createDtoFromResponse(Response $response): array
    {
        $coupons = [];

        foreach($response->json() as $coupon) {

            $coupons[] = new Coupon(
                id: $coupon['id'],
                code: $coupon['code'],
                note: $coupon['note'],
                quantity_used: $coupon['quantity_used'],
                quantity: $coupon['quantity'],
                promotion_id: $coupon['promotion_id'],
                created_at: Carbon::parse($coupon['created_at'])
            );
        }

        return $coupons;
    }

}