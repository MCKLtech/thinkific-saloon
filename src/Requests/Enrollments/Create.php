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
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Create extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateEnrollment $createEnrollment
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "enrollments";
    }

    protected function defaultBody(): array
    {
        $body = [
            'course_id' => $this->createEnrollment->course_id,
            'user_id' => $this->createEnrollment->user_id,
            'activated_at' => $this->createEnrollment->activated_at?->toIso8601String(),
            'expiry_date' => $this->createEnrollment->expiry_date?->toIso8601String()
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
            expiry_date: Carbon::parse($responseData['expiry_date']),
            credential_id: null,
            certificate_url: null,
            certificate_expiry_date: null
        );
    }

}