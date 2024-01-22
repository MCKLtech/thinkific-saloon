<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\CreatePromotion;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\Promotion;
use WooNinja\ThinkificSaloon\DataTransferObjects\Promotions\UpdatePromotion;
use WooNinja\ThinkificSaloon\Requests\Coupons\Coupons;
use WooNinja\ThinkificSaloon\Requests\Promotions\Create;
use WooNinja\ThinkificSaloon\Requests\Promotions\Delete;
use WooNinja\ThinkificSaloon\Requests\Promotions\Get;
use WooNinja\ThinkificSaloon\Requests\Promotions\Promotions;
use WooNinja\ThinkificSaloon\Requests\Promotions\Update;

class PromotionService extends Resource
{

    /**
     * Get Promotion by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Coupons/getCouponByID
     *
     * @param int $promotion_id
     * @return Promotion
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $promotion_id): Promotion
    {
        return $this->connector
            ->send(new Get($promotion_id))
            ->dtoOrFail();
    }

    /**
     * Get Promotion
     * @see https://developers.thinkific.com/api/api-documentation/#/Promotions/getPromotions
     * @return PagedPaginator
     */
    public function promotions(): PagedPaginator
    {
        return $this->connector
            ->paginate(new Promotions());
    }

    /**
     * Return the Coupons of the given Promotion
     *
     * @param int $promotion_id
     * @return PagedPaginator
     */
    public function coupons(int $promotion_id): PagedPaginator
    {
        return $this->connector
            ->paginate(new Coupons($promotion_id));
    }

    /**
     * Create a Promotion
     * @see https://developers.thinkific.com/api/api-documentation/#/Promotions/createPromotion
     *
     * @param CreatePromotion $promotion
     * @return Promotion
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreatePromotion $promotion): Promotion
    {
        return $this->connector
            ->send(new Create($promotion))
            ->dtoOrFail();
    }

    /**
     * Update a Promotion
     * @see https://developers.thinkific.com/api/api-documentation/#/Promotions/updatePromotionByID
     *
     * @param UpdatePromotion $promotion
     * @return Promotion
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function update(UpdatePromotion $promotion): Promotion
    {
        return $this->connector
            ->send(new Update($promotion))
            ->dtoOrFail();
    }

    /**
     * Delete a Promotion
     * @see https://developers.thinkific.com/api/api-documentation/#/Promotions/deletePromotionByID
     *
     * @param int $promotion_id
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function delete(int $promotion_id): Response
    {
        return $this->connector
            ->send(new Delete($promotion_id));
    }

}