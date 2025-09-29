<?php

namespace WooNinja\ThinkificSaloon\Requests\SiteScripts;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class Delete extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly string $script_id,
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "site_scripts/{$this->script_id}";
    }


}