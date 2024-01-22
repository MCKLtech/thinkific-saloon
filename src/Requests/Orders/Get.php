<?php

namespace WooNinja\ThinkificSaloon\Requests\Orders;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Orders\Order;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $order_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "orders/{$this->order_id}";
    }

    public function createDtoFromResponse(Response $response): Order
    {
        $responseData = $response->json();

        return new Order(
            id: $responseData['id'],
            created_at: Carbon::parse($responseData['created_at']),
            user_id: $responseData['user_id'],
            user_email: $responseData['user_email'],
            user_name: $responseData['user_name'],
            product_name: $responseData['product_name'],
            product_id: $responseData['product_id'],
            amount_dollars: $responseData['amount_dollars'],
            amount_cents: $responseData['amount_cents'],
            subscription: $responseData['subscription'],
            coupon_code: $responseData['coupon_code'],
            coupon_id: $responseData['coupon_id'],
            affiliate_referral_code: $responseData['affiliate_referral_code'],
            status: $responseData['status'],
            items: $responseData['items']
        );
    }
}