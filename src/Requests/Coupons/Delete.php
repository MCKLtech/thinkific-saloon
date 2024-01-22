<?php

namespace WooNinja\ThinkificSaloon\Requests\Coupons;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\Coupon;

final class Delete extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $coupon_id
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "coupons/{$this->coupon_id}";
    }


}