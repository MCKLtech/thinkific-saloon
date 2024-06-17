<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Interfaces;

use WooNinja\ThinkificSaloon\GraphQL\Connectors\ThinkificConnector;

interface Thinkific
{
    public function connector(): ThinkificConnector;

}