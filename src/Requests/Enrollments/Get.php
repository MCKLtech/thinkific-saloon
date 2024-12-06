<?php

namespace WooNinja\ThinkificSaloon\Requests\Enrollments;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\Enrollment;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $enrollment_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "enrollments/{$this->enrollment_id}";
    }

    public function createDtoFromResponse(Response $response): Enrollment
    {
        $responseData = $response->json();

        return new Enrollment(
            id: $responseData['id'],
            user_email: $responseData['user_email'],
            user_name: $responseData['user_name'],
            user_id: $responseData['user_id'],
            course_name: $responseData['course_name'],
            course_id: $responseData['course_id'],
            percentage_completed: $responseData['percentage_completed'],
            expired: $responseData['expired'],
            is_free_trial: $responseData['is_free_trial'],
            completed: $responseData['completed'],
            started_at: Carbon::parse($responseData['started_at']),
            activated_at: Carbon::parse($responseData['activated_at']),
            completed_at: Carbon::parse($responseData['completed_at']),
            updated_at: Carbon::parse($responseData['updated_at']),
            expiry_date: Carbon::parse($responseData['expiry_date']),
            credential_id: null,
            certificate_url: null,
            certificate_expiry_date: null
        );
    }
}