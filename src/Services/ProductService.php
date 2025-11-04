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
    public function get(int $product_id): ProductInterface
    {
        return $this->connector
            ->send(new Get($product_id))
            ->dtoOrFail();
    }

    /**
     * List Products
     * @see https://developers.thinkific.com/api/api-documentation/#/Products/getProducts
     *
     * @param array $filters
     * @return PagedPaginator
     */
    public function products(array $filters = []): PagedPaginator
    {
        return $this->connector
            ->paginate(new Products($filters));
    }

    /**
     * Return the Course(s) associated with a Product
     *
     * @param int $product_id
     * @return array []Course
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function courses(int $product_id): array
    {
        $product = $this->get($product_id);

        $courses = [];

        if ($product->productable_type == 'Course') {

            $courseService = new CourseService($this->service);

            $courses[] = $courseService->get($product->productable_id);
        }

        if ($product->productable_type == 'Bundle') {
            $bundleService = new BundleService($this->service);

            foreach ($bundleService->courses($product->productable_id)->items() as $course) {
                $courses[] = $course;
            }
        }

        return $courses;

    }

    /**
     * List Related Products
     * @see https://developers.thinkific.com/api/api-documentation/#/Products/getRelatedProductByProductID
     *
     * @param int $product_id
     * @return PagedPaginator
     */
    public function related(int $product_id): PagedPaginator
    {
        return $this->connector
            ->paginate(new RelatedProducts($product_id));
    }

}