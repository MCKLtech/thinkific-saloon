<?php

namespace WooNinja\ThinkificSaloon\Requests\Coupons;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\Coupon;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\CreateCoupon;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Create extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateCoupon $createCoupon
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "coupons";
    }

    protected function defaultBody(): array
    {
        $body = [
            'code' => $this->createCoupon->code,
            'note' => $this->createCoupon->note ?? null,
            'quantity' => $this->createCoupon->quantity ?? null,
        ];

        return $this->removeEmptyArrayValues($body);
    }

    protected function defaultQuery(): array
    {
        return [
            'promotion_id' => $this->createCoupon->promotion_id
        ];
    }

    public function createDtoFromResponse(Response $response): Coupon
    {
        $responseData = $response->json();

        return new Coupon(
            id: $responseData['id'],
            code: $responseData['code'],
            note: $responseData['note'],
            quantity_used: $responseData['quantity_used'],
            quantity: $responseData['quantity'],
            promotion_id: $responseData['promotion_id'],
            created_at: Carbon::parse($responseData['created_at'])

        );
    }

}