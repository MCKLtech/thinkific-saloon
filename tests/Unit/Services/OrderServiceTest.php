<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Orders\Order;
use WooNinja\ThinkificSaloon\Requests\Orders\Get;
use WooNinja\ThinkificSaloon\Requests\Orders\Orders;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class OrderServiceTest extends TestCase
{
    public function test_can_get_order_by_id(): void
    {
        $orderData = $this->mockOrderData(['id' => 42, 'amount_dollars' => 199.5]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($orderData, 200),
        ]);

        $order = $this->service->orders->get(42);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(42, $order->id);
        $this->assertEquals(199.5, $order->amount_dollars);
        $this->assertEquals('complete', $order->status);
    }

    public function test_can_list_orders(): void
    {
        $orders = [
            $this->mockOrderData(['id' => 1]),
            $this->mockOrderData(['id' => 2]),
        ];

        $this->mockGlobalRequests([
            Orders::class => MockResponse::make([
                'items' => $orders,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $paginator = $this->service->orders->orders();
        $result = iterator_to_array($paginator->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Order::class, $result[0]);
    }

    public function test_list_orders_falls_back_to_first_item_when_product_name_and_id_are_empty(): void
    {
        $orderData = $this->mockOrderData([
            'id' => 3,
            'product_name' => '',
            'product_id' => null,
            'items' => [
                ['product_name' => 'Bundled Course', 'product_id' => 99],
            ],
        ]);

        $this->mockGlobalRequests([
            Orders::class => MockResponse::make([
                'items' => [$orderData],
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 1, 'entries_info' => '1-1 of 1',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->orders->orders()->items());

        $this->assertEquals('Bundled Course', $result[0]->product_name);
        $this->assertEquals(99, $result[0]->product_id);
    }
}
