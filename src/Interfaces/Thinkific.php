<?php

namespace WooNinja\ThinkificSaloon\Interfaces;

use WooNinja\ThinkificSaloon\Connectors\ThinkificConnector;

interface Thinkific
{
    public function connector(): ThinkificConnector;

}