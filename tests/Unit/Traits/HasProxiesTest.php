<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;
use WooNinja\ThinkificSaloon\Connectors\ThinkificConnector;
use WooNinja\ThinkificSaloon\Senders\ProxySender;

/**
 * HasProxies has no standalone implementer - it's exercised here through
 * ThinkificConnector, the concrete class that uses it and wires
 * isUsingProxy()/getProxyUrl() into defaultSender().
 */
class HasProxiesTest extends TestCase
{
    public function test_is_not_using_a_proxy_by_default(): void
    {
        $connector = new ThinkificConnector('acme');

        $this->assertFalse($connector->isUsingProxy());
        $this->assertNull($connector->getProxyUrl());
    }

    public function test_set_proxy_url_enables_proxy_usage(): void
    {
        $connector = new ThinkificConnector('acme');

        $result = $connector->setProxyUrl('http://proxy.example.com:8080');

        $this->assertSame($connector, $result);
        $this->assertTrue($connector->isUsingProxy());
        $this->assertEquals('http://proxy.example.com:8080', $connector->getProxyUrl());
    }

    public function test_connector_uses_a_proxy_sender_once_a_proxy_url_is_set(): void
    {
        $connector = new ThinkificConnector('acme');
        $connector->setProxyUrl('http://proxy.example.com:8080');

        $this->assertInstanceOf(ProxySender::class, $connector->sender());
    }

    public function test_connector_does_not_use_a_proxy_sender_without_a_proxy_url(): void
    {
        $connector = new ThinkificConnector('acme');

        $this->assertNotInstanceOf(ProxySender::class, $connector->sender());
    }
}
