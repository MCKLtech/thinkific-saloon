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
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\UpdatePromotion;
use WooNinja\ThinkificSaloon\Traits\RequestTrait;

final class Update extends Request implements HasBody
{
    use HasJsonBody;
    use RequestTrait;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly UpdatePromotion $updatePromotion
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "promotions/{$this->updatePromotion->promotion_id}";
    }

    protected function defaultBody(): array
    {
        $body = [
            'name' => $this->updatePromotion->name,
            'description' => $this->updatePromotion->description ?? null,
            'starts_at' => $this->updatePromotion->starts_at?->toIso8601String(),
            'expires_at' => $this->updatePromotion->expires_at?->toIso8601String(),
            'discount_type' => $this->updatePromotion->discount_type,
            'amount' => $this->updatePromotion->amount,
            'coupon_ids' => $this->updatePromotion->coupon_ids ?? null,
            'product_ids' => $this->updatePromotion->product_ids ?? null,
            'duration' => $this->updatePromotion->duration ?? null,
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
            expires_at: $responseData['expires_at'] ? Carbon::parse($responseData['expires_at']) : null,
            discount_type: $responseData['discount_type'],
            amount: $responseData['amount'],
            coupon_ids: $responseData['coupon_ids'],
            product_ids: $responseData['product_ids'] ?? [],
            duration: $responseData['duration']
        );
    }

}