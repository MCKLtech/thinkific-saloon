<?php

namespace WooNinja\ThinkificSaloon\Requests\SiteScripts;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\SiteScript;

final class Scripts extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return "site_scripts";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $script) {
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
        }, $response->json('items'));
    }
}