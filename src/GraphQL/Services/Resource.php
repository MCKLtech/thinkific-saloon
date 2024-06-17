<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use WooNinja\ThinkificSaloon\GraphQL\Connectors\ThinkificConnector;


abstract class Resource
{
    /**
     * @var ThinkificGraphQLService
     */
    protected ThinkificGraphQLService $service;

    protected ThinkificConnector $connector;

    /**
     * IntercomService constructor.
     *
     * @param ThinkificGraphQLService $service
     */
    public function __construct(ThinkificGraphQLService $service)
    {
        $this->service = $service;

        $this->connector = $this->service->connector();
    }

}