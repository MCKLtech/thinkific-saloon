<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\BulkCreateCoupon;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\Coupon;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\CreateCoupon;
use WooNinja\ThinkificSaloon\DataTransferObjects\Coupons\UpdateCoupon;
use WooNinja\ThinkificSaloon\Requests\Coupons\BulkCreate;
use WooNinja\ThinkificSaloon\Requests\Coupons\Coupons;
use WooNinja\ThinkificSaloon\Requests\Coupons\Create;
use WooNinja\ThinkificSaloon\Requests\Coupons\Delete;
use WooNinja\ThinkificSaloon\Requests\Coupons\Get;
use WooNinja\ThinkificSaloon\Requests\Coupons\Update;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class CouponServiceTest extends TestCase
{
    public function test_can_get_coupon_by_id(): void
    {
        $couponData = $this->mockCouponData(['id' => 5, 'code' => 'SAVE50']);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($couponData, 200),
        ]);

        $coupon = $this->service->coupons->get(5);

        $this->assertInstanceOf(Coupon::class, $coupon);
        $this->assertEquals(5, $coupon->id);
        $this->assertEquals('SAVE50', $coupon->code);
    }

    public function test_can_list_coupons_for_a_promotion(): void
    {
        $coupons = [
            $this->mockCouponData(['id' => 1]),
            $this->mockCouponData(['id' => 2]),
        ];

        $this->mockGlobalRequests([
            Coupons::class => MockResponse::make([
                'items' => $coupons,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->coupons->coupons(1)->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Coupon::class, $result[0]);
    }

    public function test_can_create_coupon(): void
    {
        $couponData = $this->mockCouponData(['id' => 9, 'code' => 'NEW10']);

        $this->mockGlobalRequests([
            Create::class => MockResponse::make($couponData, 201),
        ]);

        $coupon = $this->service->coupons->create(new CreateCoupon(
            promotion_id: 1,
            code: 'NEW10',
            quantity: 50,
            note: null,
        ));

        $this->assertEquals(9, $coupon->id);
        $this->assertEquals('NEW10', $coupon->code);
    }

    public function test_can_bulk_create_coupons(): void
    {
        // BulkCreate reads a raw array from the response body, not items/meta.
        $this->mockGlobalRequests([
            BulkCreate::class => MockResponse::make([
                $this->mockCouponData(['id' => 1, 'code' => 'BULK-AAA']),
                $this->mockCouponData(['id' => 2, 'code' => 'BULK-BBB']),
            ], 201),
        ]);

        $coupons = $this->service->coupons->bulkCreate(new BulkCreateCoupon(
            promotion_id: 1,
            bulk_quantity_per_coupon: 1,
            bulk_coupon_code_length: 8,
            bulk_quantity: 2,
        ));

        $this->assertCount(2, $coupons);
        $this->assertInstanceOf(Coupon::class, $coupons[0]);
        $this->assertEquals('BULK-AAA', $coupons[0]->code);
    }

    public function test_can_update_coupon(): void
    {
        $this->mockGlobalRequests([
            Update::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->coupons->update(new UpdateCoupon(
            coupon_id: 5,
            code: 'UPDATED',
            note: null,
            quantity: 25,
            quantity_used: null,
        ));

        $this->assertTrue($response->successful());
    }

    public function test_can_delete_coupon(): void
    {
        $this->mockGlobalRequests([
            Delete::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->coupons->delete(5);

        $this->assertTrue($response->successful());
    }

    /**
     * Regression guard: Thinkific's UpdateCoupon schema requires code, even
     * though every other field is optional. A nullable code lets a caller
     * construct a request the real API will reject with a 422, so it must
     * stay a required (non-nullable, no default) constructor argument.
     */
    public function test_code_is_required_on_update_coupon_dto(): void
    {
        $params = (new \ReflectionClass(UpdateCoupon::class))->getConstructor()->getParameters();
        $code = current(array_filter($params, fn(\ReflectionParameter $p) => $p->getName() === 'code'));

        $this->assertNotFalse($code, 'UpdateCoupon should have a $code constructor parameter');
        $this->assertFalse($code->allowsNull(), 'UpdateCoupon::$code should not be nullable');
        $this->assertFalse($code->isDefaultValueAvailable(), 'UpdateCoupon::$code should not have a default value');
    }
}
