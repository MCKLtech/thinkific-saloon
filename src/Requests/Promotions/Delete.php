<?php

namespace WooNinja\ThinkificSaloon\Requests\Promotions;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\Promotion;

final class Delete extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $promotion_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "promotions/{$this->promotion_id}";
    }


}