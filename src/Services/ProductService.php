<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Products\Product;
use WooNinja\ThinkificSaloon\Requests\Products\Get;
use WooNinja\ThinkificSaloon\Requests\Products\Products;
use WooNinja\ThinkificSaloon\Requests\Products\RelatedProducts;

class ProductService extends Resource
{

    /**
     * Get Products by ID
     *
     * @param int $product_id
     * @return Product
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $product_id): Product
    {
        return $this->connector
            ->send(new Get($product_id))
            ->dtoOrFail();
    }

    /**
     * List Products
     * @see https://developers.thinkific.com/api/api-documentation/#/Products/getProducts
     *
     * @return PagedPaginator
     */
    public function products(): PagedPaginator
    {
        return $this->connector
            ->paginate(new Products());
    }

    /**
     * List Related Products
     * @see https://developers.thinkific.com/api/api-documentation/#/Products/getRelatedProductByProductID
     *
     * @return PagedPaginator
     */
    public function related(int $product_id): PagedPaginator
    {
        return $this->connector
            ->paginate(new RelatedProducts($product_id));
    }

}