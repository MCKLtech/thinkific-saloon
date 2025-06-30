<?php

namespace WooNinja\ThinkificSaloon\Senders;

use GuzzleHttp\Client;
use Saloon\Http\Senders\GuzzleSender;
use GuzzleHttp\HandlerStack;

class ProxySender extends GuzzleSender
{
    /**
     * @param string $proxyUrl The proxy URL to use for requests
     * @param bool $verifySsl Whether to verify SSL certificates
     * @param array $defaultConfig Additional default Guzzle configuration
     */
    public function __construct(
        private string $proxyUrl,
        private bool   $verifySsl = true,
        array          $defaultConfig = []
    )
    {
        parent::__construct();

        // Merge any additional default configuration
        if (!empty($defaultConfig)) {
            $this->client = new Client(array_merge([
                'proxy' => $this->proxyUrl,
                'verify' => $this->verifySsl,
                'handler' => $this->handlerStack,
            ], $defaultConfig));
        }
    }

    /**
     * Create the Guzzle client with proxy settings
     */
    protected function createGuzzleClient(): Client
    {
        $this->handlerStack = HandlerStack::create();

        return new Client([
            'proxy' => $this->proxyUrl,
            'verify' => $this->verifySsl,
            'handler' => $this->handlerStack
        ]);
    }

    /**
     * Update the proxy URL after instantiation
     */
    public function setProxyUrl(string $proxyUrl): self
    {
        $this->proxyUrl = $proxyUrl;
        $this->client = $this->createGuzzleClient();

        return $this;
    }

    /**
     * Update SSL verification after instantiation
     */
    public function setVerifySsl(bool $verifySsl): self
    {
        $this->verifySsl = $verifySsl;
        $this->client = $this->createGuzzleClient();

        return $this;
    }
}