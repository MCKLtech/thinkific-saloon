<?php

namespace WooNinja\ThinkificSaloon\Requests\Orders;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Orders\Order;

final class Orders extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return "orders";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $order) {
            // Fallback to first item in items array if product_name or product_id are empty
            $productName = $order['product_name'];
            $productId = $order['product_id'];

            if ((empty($productName) || empty($productId)) && !empty($order['items']) && is_array($order['items']) && isset($order['items'][0])) {
                $firstItem = $order['items'][0];
                if (empty($productName)) {
                    $productName = $firstItem['product_name'] ?? 'Unknown Product';
                }
                if (empty($productId)) {
                    $productId = $firstItem['product_id'] ?? 0;
                }
            }

            // Final fallback if still empty
            $productName = $productName ?? 'Unknown Product';
            $productId = $productId ?? 0;

            return new Order(
                id: $order['id'],
                created_at: Carbon::parse($order['created_at']),
                user_id: $order['user_id'],
                user_email: $order['user_email'],
                user_name: $order['user_name'],
                product_name: $productName,
                product_id: $productId,
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