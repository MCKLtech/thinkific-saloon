<?php

namespace WooNinja\ThinkificSaloon\Requests\Bundles;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\Enrollment;

final class Enrollments extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $productable_id,
        private readonly array $filters = [],
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "bundles/{$this->productable_id}/enrollments";
    }

    protected function defaultQuery(): array
    {
        return $this->filters;
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $enrollment) {
            return new Enrollment(
                id: $enrollment['id'],
                user_email: $enrollment['user_email'],
                user_name: $enrollment['user_name'],
                user_id: $enrollment['user_id'],
                course_name: $enrollment['course_name'],
                course_id: $enrollment['course_id'],
                percentage_completed: $enrollment['percentage_completed'],
                expired: $enrollment['expired'],
                is_free_trial: $enrollment['is_free_trial'],
                completed: $enrollment['completed'],
                started_at: Carbon::parse($enrollment['started_at']),
                activated_at: Carbon::parse($enrollment['activated_at']),
                completed_at: Carbon::parse($enrollment['completed_at']),
                updated_at: Carbon::parse($enrollment['updated_at']),
                expiry_date: Carbon::parse($enrollment['expiry_date']),
                credential_id: null,
                certificate_url: null,
                certificate_expiry_date: null
            );
        }, $response->json('items'));
    }
}