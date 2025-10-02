<?php

namespace WooNinja\ThinkificSaloon\Requests\SiteScripts;


use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\SiteScript;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\UpdateSiteScript;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Update extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly UpdateSiteScript $updateScript
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "site_scripts/{$this->updateScript->id}";
    }

    protected function defaultBody(): array
    {
        $body = [
            'content' => $this->updateScript->content,
            'src' => $this->updateScript->src,
            'name' => $this->updateScript->name,
            'description' => $this->updateScript->description,
            'page_scopes' => $this->updateScript->page_scopes,
            'location' => $this->updateScript->location,
            'load_method' => $this->updateScript->load_method,
            'category' => $this->updateScript->category,
        ];

        return $this->removeEmptyArrayValues($body);
    }


    public function createDtoFromResponse(Response $response): SiteScript
    {
        $script = $response->json();

        return new SiteScript(
            id: $script['id'],
            name: $script['name'],
            description: $script['description'],
            page_scopes: $script['page_scopes'],
            location: $script['location'],
            load_method: $script['load_method'],
            category: $script['category'],
            content: $script['content'] ?? null,
            src: $script['src'] ?? null
        );
    }

}