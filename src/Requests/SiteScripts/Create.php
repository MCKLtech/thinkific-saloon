<?php

namespace WooNinja\ThinkificSaloon\Requests\SiteScripts;


use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\CreateSiteScript;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\SiteScript;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Create extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateSiteScript $createScript
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "site_scripts";
    }

    protected function defaultBody(): array
    {
        $body = [
            'content' => $this->createScript->content,
            'src' => $this->createScript->src,
            'name' => $this->createScript->name,
            'description' => $this->createScript->description,
            'page_scopes' => $this->createScript->page_scopes,
            'location' => $this->createScript->location,
            'load_method' => $this->createScript->load_method,
            'category' => $this->createScript->category,
        ];

        return $this->removeEmptyArrayValues($body);
    }


    public function createDtoFromResponse(Response $response): SiteScript
    {
        $script = $response->json('site_script');

        return new SiteScript(
            id: $script['id'],
            name: $script['name'],
            description: $script['description'],
            page_scopes: $script['page_scopes'],
            location: $script['location'],
            load_method: $script['load_method'],
            category: $script['category'],
            content: $script['content'] ?? null,
            src: $script['src'] ?? null,
        );
    }

}