<?php

namespace WooNinja\ThinkificSaloon\Requests\SiteScripts;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\SiteScript;

final class Get extends Request
{

    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $script_id
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "site_scripts/{$this->script_id}";
    }

    public function createDtoFromResponse(Response $response): SiteScript
    {
        $script = $response->json();

        return new SiteScript(
            id: $script['id'],
            content: $script['content'],
            name: $script['name'],
            description: $script['description'],
            page_scopes: $script['page_scopes'],
            location: $script['location'],
            load_method: $script['load_method'],
            category: $script['category']
        );
    }
}