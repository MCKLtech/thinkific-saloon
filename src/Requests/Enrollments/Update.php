<?php

namespace WooNinja\ThinkificSaloon\Requests\Enrollments;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
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

}