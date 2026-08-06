<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Senders;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Saloon\Http\Senders\GuzzleSender;
use WooNinja\ThinkificSaloon\Senders\ProxySender;

class ProxySenderTest extends TestCase
{
    /**
     * ProxySender's Guzzle client is a protected property inherited from
     * GuzzleSender with no public getter, so reflection is the only way to
     * confirm the proxy config actually reached the underlying client.
     */
    private function guzzleClientConfig(ProxySender $sender, string $option)
    {
        $client = (new ReflectionClass(GuzzleSender::class))->getProperty('client');
        $client->setAccessible(true);

        return $client->getValue($sender)->getConfig($option);
    }

    public function test_is_a_guzzle_sender(): void
    {
        $sender = new ProxySender('http://proxy.example.com:8080');

        $this->assertInstanceOf(GuzzleSender::class, $sender);
    }

    public function test_guzzle_client_is_configured_with_the_given_proxy(): void
    {
        $sender = new ProxySender('http://proxy.example.com:8080');

        $this->assertEquals('http://proxy.example.com:8080', $this->guzzleClientConfig($sender, 'proxy'));
    }

    public function test_ssl_verification_defaults_to_true(): void
    {
        $sender = new ProxySender('http://proxy.example.com:8080');

        $this->assertTrue($this->guzzleClientConfig($sender, 'verify'));
    }

    public function test_ssl_verification_can_be_disabled(): void
    {
        $sender = new ProxySender('http://proxy.example.com:8080', verifySsl: false);

        $this->assertFalse($this->guzzleClientConfig($sender, 'verify'));
    }

    public function test_set_proxy_url_updates_the_underlying_client(): void
    {
        $sender = new ProxySender('http://proxy.example.com:8080');

        $result = $sender->setProxyUrl('http://other-proxy.example.com:9090');

        $this->assertSame($sender, $result);
        $this->assertEquals('http://other-proxy.example.com:9090', $this->guzzleClientConfig($sender, 'proxy'));
    }

    public function test_set_verify_ssl_updates_the_underlying_client(): void
    {
        $sender = new ProxySender('http://proxy.example.com:8080');

        $result = $sender->setVerifySsl(false);

        $this->assertSame($sender, $result);
        $this->assertFalse($this->guzzleClientConfig($sender, 'verify'));
    }
}
