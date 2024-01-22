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
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\UpdateCoupon;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Update extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly UpdateCoupon $updateCoupon
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "coupons/{$this->updateCoupon->coupon_id}";
    }

    protected function defaultBody(): array
    {
        $body = [
            'code' => $this->updateCoupon->code,
            'note' => $this->updateCoupon->note ?? null,
            'quantity' => $this->updateCoupon->quantity ?? null,
            'quantity_used' => $this->updateCoupon->quantity_used ?? null,
        ];

        return $this->removeEmptyArrayValues($body);
    }

}