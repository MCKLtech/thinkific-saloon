<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\Connectors\ThinkificConnector;

abstract class Resource
{
    /**
     * @var ThinkificService
     */
    protected ThinkificService $service;

    protected ThinkificConnector $connector;

    /**
     * IntercomService constructor.
     *
     * @param ThinkificService $service
     */
    public function __construct(ThinkificService $service)
    {
        $this->service = $service;

        $this->connector = $this->service->connector();
    }

    /**
     * Determine whether a paginated query returns at least one result,
     * without loading more than a single item off the wire.
     *
     * @param PagedPaginator $paginator
     * @return bool
     */
    protected function paginatorHasAnyItems(PagedPaginator $paginator): bool
    {
        foreach ($paginator->items() as $item) {
            return true;
        }

        return false;
    }

}