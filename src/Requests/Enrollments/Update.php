<?php

namespace WooNinja\ThinkificSaloon\Requests\Enrollments;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\CreateEnrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\Enrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\UpdateEnrollment;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Update extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly UpdateEnrollment $updateEnrollment
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "enrollments/{$this->updateEnrollment->enrollment_id}";
    }

    protected function defaultBody(): array
    {
        $body = [
            'activated_at' => $this->updateEnrollment->activated_at?->toIso8601String(),
            'expiry_date' => $this->updateEnrollment->expiry_date?->toIso8601String()
        ];

        return $this->removeEmptyArrayValues($body);
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
            expiry_date: Carbon::parse($responseData['expiry_date'])
        );
    }

}