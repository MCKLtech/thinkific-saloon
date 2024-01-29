<?php

namespace WooNinja\ThinkificSaloon\Requests\Bundles;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\CreateBundleEnrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Bundles\UpdateBundleEnrollment;

final class UpdateEnrollment extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly UpdateBundleEnrollment $updateBundleEnrollment,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "bundles/{$this->updateBundleEnrollment->productable_id}/enrollments";
    }

    protected function defaultBody(): array
    {
        $body = [
            'user_id' => $this->updateBundleEnrollment->user_id,
            'activated_at' => $this->updateBundleEnrollment->activated_at?->toIso8601String(),
            'expiry_date' => $this->updateBundleEnrollment->expiry_date?->toIso8601String(),
        ];

        return array_filter($body, fn($value) => $value !== null);
    }

}