<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\PaginationPlugin\PagedPaginator;
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

class CouponService extends Resource
{

    /**
     * Get Coupon by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Coupons/getCouponByID
     *
     * @param int $coupon_id
     * @return Coupon
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $coupon_id): Coupon
    {
        return $this->connector
            ->send(new Get($coupon_id))
            ->dtoOrFail();
    }

    /**
     * Get Coupons for a given Promotion
     * @see https://developers.thinkific.com/api/api-documentation/#/Coupons/getCoupons
     * @param int $promotion_id
     * @return PagedPaginator
     */
    public function coupons(int $promotion_id): PagedPaginator
    {
        return $this->connector
            ->paginate(new Coupons($promotion_id));
    }

    /**
     * Create a Coupon for a given Promotion
     * @see https://developers.thinkific.com/api/api-documentation/#/Coupons/createCoupon
     *
     * @param CreateCoupon $coupon
     * @return Coupon
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateCoupon $coupon): Coupon
    {
        return $this->connector
            ->send(new Create($coupon))
            ->dtoOrFail();
    }

    /**
     * Create coupons in bulk
     * @see https://developers.thinkific.com/api/api-documentation/#/Coupons/bulkCreateCoupons
     *
     * @param BulkCreateCoupon $coupon
     * @return array
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function bulkCreate(BulkCreateCoupon $coupon): array
    {
        return $this->connector
            ->send(new BulkCreate($coupon))
            ->dtoOrFail();
    }

    /**
     * Update a Coupon
     * @https://developers.thinkific.com/api/api-documentation/#/Coupons/updateCoupon
     *
     * @param UpdateCoupon $coupon
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function update(UpdateCoupon $coupon)
    {
        return $this->connector
            ->send(new Update($coupon));
    }

    /**
     * Delete a Coupon
     * @see https://developers.thinkific.com/api/api-documentation/#/Coupons/deleteCouponByID
     *
     * @param int $coupon_id
     * @return \Saloon\Http\Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function delete(int $coupon_id)
    {
        return $this->connector
            ->send(new Delete($coupon_id));
    }


}