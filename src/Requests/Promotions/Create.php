<?php

namespace WooNinja\ThinkificSaloon\Requests\Promotions;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\Coupon;

use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\CreatePromotion;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\Promotion;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Create extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreatePromotion $createPromotion
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "promotions";
    }

    protected function defaultBody(): array
    {
        $body = [
            'name' => $this->createPromotion->name,
            'description' => $this->createPromotion->description ?? null,
            'starts_at' => $this->createPromotion->starts_at?->toIso8601String(),
            'expires_at' => $this->createPromotion->expires_at?->toIso8601String(),
            'discount_type' => $this->createPromotion->discount_type,
            'amount' => $this->createPromotion->amount,
            'coupon_ids' => $this->createPromotion->coupon_ids ?? null,
            'product_ids' => $this->createPromotion->product_ids ?? null,
            'duration' => $this->createPromotion->duration ?? null,
        ];

        return $this->removeEmptyArrayValues($body);
    }


    public function createDtoFromResponse(Response $response): Promotion
    {
        $responseData = $response->json();

        return new Promotion(
            id: $responseData['id'],
            name: $responseData['name'],
            description: $responseData['description'],
            starts_at: Carbon::parse($responseData['starts_at']),
            expires_at: Carbon::parse($responseData['expires_at']),
            discount_type: $responseData['discount_type'],
            amount: $responseData['amount'],
            coupon_ids: $responseData['coupon_ids'],
            product_ids: $responseData['product_ids'] ?? [],
            duration: $responseData['duration']
        );
    }

}