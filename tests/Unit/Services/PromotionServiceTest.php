<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\CreatePromotion;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\Promotion;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\UpdatePromotion;
use WooNinja\ThinkificSaloon\Requests\Coupons\Coupons;
use WooNinja\ThinkificSaloon\Requests\Promotions\Create;
use WooNinja\ThinkificSaloon\Requests\Promotions\Delete;
use WooNinja\ThinkificSaloon\Requests\Promotions\Get;
use WooNinja\ThinkificSaloon\Requests\Promotions\Promotions;
use WooNinja\ThinkificSaloon\Requests\Promotions\Update;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class PromotionServiceTest extends TestCase
{
    public function test_can_get_promotion_by_id(): void
    {
        $promotionData = $this->mockPromotionData(['id' => 3, 'name' => 'Summer Sale']);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($promotionData, 200),
        ]);

        $promotion = $this->service->promotions->get(3);

        $this->assertInstanceOf(Promotion::class, $promotion);
        $this->assertEquals(3, $promotion->id);
        $this->assertEquals('Summer Sale', $promotion->name);
    }

    public function test_can_list_promotions(): void
    {
        $promotions = [
            $this->mockPromotionData(['id' => 1]),
            $this->mockPromotionData(['id' => 2]),
        ];

        $this->mockGlobalRequests([
            Promotions::class => MockResponse::make([
                'items' => $promotions,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->promotions->promotions()->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Promotion::class, $result[0]);
    }

    public function test_can_list_coupons_for_a_promotion(): void
    {
        $coupons = [$this->mockCouponData(['id' => 1, 'promotion_id' => 3])];

        $this->mockGlobalRequests([
            Coupons::class => MockResponse::make([
                'items' => $coupons,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 1, 'entries_info' => '1-1 of 1',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->promotions->coupons(3)->items());

        $this->assertCount(1, $result);
    }

    public function test_can_create_promotion(): void
    {
        $promotionData = $this->mockPromotionData(['id' => 9, 'name' => 'New Promo']);

        $this->mockGlobalRequests([
            Create::class => MockResponse::make($promotionData, 201),
        ]);

        $promotion = $this->service->promotions->create(new CreatePromotion(
            name: 'New Promo',
            description: 'A promotion',
            starts_at: null,
            expires_at: null,
            discount_type: 'percentage',
            amount: 20,
            product_ids: [1],
            coupon_ids: null,
            duration: null,
        ));

        $this->assertEquals(9, $promotion->id);
        $this->assertEquals('New Promo', $promotion->name);
    }

    public function test_can_update_promotion(): void
    {
        $this->mockGlobalRequests([
            Update::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->promotions->update(new UpdatePromotion(
            promotion_id: 3,
            name: 'Updated Promo',
            description: null,
            starts_at: null,
            expires_at: null,
            discount_type: 'percentage',
            amount: 30,
            product_ids: null,
            coupon_ids: null,
            duration: null,
        ));

        $this->assertTrue($response->successful());
    }

    public function test_can_delete_promotion(): void
    {
        $this->mockGlobalRequests([
            Delete::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->promotions->delete(3);

        $this->assertTrue($response->successful());
    }
}
