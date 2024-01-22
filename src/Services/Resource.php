<?php

namespace WooNinja\ThinkificSaloon\Services;

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

}