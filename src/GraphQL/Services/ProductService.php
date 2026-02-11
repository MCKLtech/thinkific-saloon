<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Products\ProductsWithCertificates;

class ProductService extends Resource
{
    /**
     * Return Products with Certificate Information
     *
     * @param int $per_page
     * @return Paginator
     */
    public function productsWithCertificates(int $per_page = 100): Paginator
    {
        $products = new ProductsWithCertificates($per_page);

        return $products->paginate($this->connector);
    }
}
