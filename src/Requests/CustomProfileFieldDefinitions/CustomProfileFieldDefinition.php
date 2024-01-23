<?php

namespace WooNinja\ThinkificSaloon\Requests\CustomProfileFieldDefinitions;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use \WooNinja\ThinkificSaloon\DataTransferObjects\CustomProfileFieldDefinitions\CustomProfileFieldDefinition as CustomProfileFieldDefinitionDto;

final class CustomProfileFieldDefinition extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return "custom_profile_field_definitions";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $enrollment) {
            return new CustomProfileFieldDefinitionDto(
                id: $enrollment['id'],
                label: $enrollment['label'],
                field_type: $enrollment['field_type'],
                required: $enrollment['required'],
            );
        }, $response->json('items'));
    }
}