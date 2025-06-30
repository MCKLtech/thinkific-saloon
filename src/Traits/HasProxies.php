<?php

namespace WooNinja\ThinkificSaloon\Traits;

trait HasProxies
{
    protected ?string $proxyUrl = null;

    public function setProxyUrl(string $proxyUrl): static
    {
        $this->proxyUrl = $proxyUrl;
        return $this;
    }

    public function isUsingProxy(): bool
    {
        return !empty($this->proxyUrl);
    }

    public function getProxyUrl(): ?string
    {
        return $this->proxyUrl;
    }
}