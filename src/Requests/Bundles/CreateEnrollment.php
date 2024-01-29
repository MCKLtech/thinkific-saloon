<?php

namespace WooNinja\ThinkificSaloon\Requests\Bundles;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\CreateBundleEnrollment;

final class CreateEnrollment extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateBundleEnrollment $createBundleEnrollment,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "bundles/{$this->createBundleEnrollment->productable_id}/enrollments";
    }

    protected function defaultBody(): array
    {
        $body = [
            'user_id' => $this->createBundleEnrollment->user_id,
            'activated_at' => $this->createBundleEnrollment->activated_at?->toIso8601String(),
            'expiry_date' => $this->createBundleEnrollment->expiry_date?->toIso8601String(),
        ];

        return array_filter($body, fn($value) => $value !== null);
    }

}