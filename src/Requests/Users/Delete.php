<?php

namespace WooNinja\ThinkificSaloon\Requests\Users;

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
        private readonly int $user_id
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "users/{$this->user_id}";
    }


}