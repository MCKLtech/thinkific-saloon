<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Orders\Order;
use WooNinja\ThinkificSaloon\Requests\Orders\Get;
use WooNinja\ThinkificSaloon\Requests\Orders\Orders;

class OrderService extends Resource
{

    public function get(int $order_id): Order
    {
        return $this->connector
            ->send(new Get($order_id))
            ->dtoOrFail();
    }

    public function orders(): PagedPaginator
    {
        return $this->connector
            ->paginate(new Orders());
    }

}