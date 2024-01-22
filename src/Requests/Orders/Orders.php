<?php

namespace WooNinja\ThinkificSaloon\Requests\Orders;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\Enrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Orders\Order;
use WooNinja\ThinkificSaloon\DataTransferObjects\Products\Product;

final class Orders extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return "orders";
    }

    public function createDtoFromResponse(Response $response): array
    {
        ray($response);

        return array_map(function (array $order) {
            return new Order(
                id: $order['id'],
                created_at: Carbon::parse($order['created_at']),
                user_id: $order['user_id'],
                user_email: $order['user_email'],
                user_name: $order['user_name'],
                product_name: $order['product_name'],
                product_id: $order['product_id'],
                amount_dollars: $order['amount_dollars'],
                amount_cents: $order['amount_cents'],
                subscription: $order['subscription'],
                coupon_code: $order['coupon_code'],
                coupon_id: $order['coupon_id'],
                affiliate_referral_code: $order['affiliate_referral_code'],
                status: $order['status'],
                items: $order['items']
            );
        }, $response->json('items'));
    }
}