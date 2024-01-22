<?php

namespace WooNinja\ThinkificSaloon\Requests\Bundles;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\Bundle;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $productable_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "bundles/{$this->productable_id}";
    }

    public function createDtoFromResponse(Response $response): Bundle
    {
        $responseData = $response->json();

        return new Bundle(
            id: $responseData['id'],
            name: $responseData['name'],
            description: $responseData['description'],
            banner_image_url: $responseData['banner_image_url'],
            course_ids: $responseData['course_ids'],
            bundle_card_image_url: $responseData['bundle_card_image_url'],
            tagline: $responseData['tagline'],
            slug: $responseData['slug'],
        );
    }
}