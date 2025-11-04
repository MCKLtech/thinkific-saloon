<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\LMSContracts\Contracts\Services\OrderServiceInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Orders\OrderInterface;
use WooNinja\ThinkificSaloon\DataTransferObjects\Orders\Order;
use WooNinja\ThinkificSaloon\Requests\Orders\Get;
use WooNinja\ThinkificSaloon\Requests\Orders\Orders;

class OrderService extends Resource implements OrderServiceInterface
{

    public function get(int $order_id): OrderInterface
    {
        return $this->connector
            ->send(new Get($order_id))
            ->dtoOrFail();
    }

    public function orders(array $filters = []): PagedPaginator
    {
        return $this->connector
            ->paginate(new Orders());
    }

}