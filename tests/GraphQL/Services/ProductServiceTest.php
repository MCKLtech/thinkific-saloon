<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Products\Product;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Products\ProductsWithCertificates;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

class ProductServiceTest extends GraphQLTestCase
{
    // -------------------------------------------------------------------------
    // productsWithCertificates() — paginated via edges
    // -------------------------------------------------------------------------

    public function test_returns_product_dtos(): void
    {
        $this->mockGql([
            ProductsWithCertificates::class => $this->gqlResponse([
                'site' => ['products' => $this->gqlEdgeConnection([$this->gqlProductWithCertificates()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->products->productsWithCertificates()->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Product::class, $items[0]);
        $this->assertEquals('prod_101', $items[0]->id);
        $this->assertEquals('Intro to PHP', $items[0]->name);
        $this->assertEquals('intro-to-php', $items[0]->slug);
        $this->assertEquals('PUBLISHED', $items[0]->status);
    }

    public function test_course_product_with_certificates_sets_has_certificates_true(): void
    {
        $product = $this->gqlProductWithCertificates(['item' => [
            'id'           => '101',
            'name'         => 'intro-to-php',
            'slug'         => 'intro-to-php',
            'certificates' => ['totalCount' => 5],
        ]]);

        $this->mockGql([
            ProductsWithCertificates::class => $this->gqlResponse([
                'site' => ['products' => $this->gqlEdgeConnection([$product])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->products->productsWithCertificates()->items());

        $this->assertTrue($items[0]->hasCertificates);
    }

    public function test_course_product_with_zero_certificates_sets_has_certificates_false(): void
    {
        $product = $this->gqlProductWithCertificates(['item' => [
            'id'           => '101',
            'name'         => 'intro-to-php',
            'slug'         => 'intro-to-php',
            'certificates' => ['totalCount' => 0],
        ]]);

        $this->mockGql([
            ProductsWithCertificates::class => $this->gqlResponse([
                'site' => ['products' => $this->gqlEdgeConnection([$product])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->products->productsWithCertificates()->items());

        $this->assertFalse($items[0]->hasCertificates);
    }

    public function test_bundle_product_has_certificates_false_regardless_of_item(): void
    {
        // Bundles cannot hold certificates — itemType drives the check
        $bundleProduct = [
            'id'             => 'prod_200',
            'name'           => 'Pro Bundle',
            'slug'           => 'pro-bundle',
            'status'         => 'PUBLISHED',
            'itemType'       => 'BUNDLE',
            'item'           => [],   // fragment won't match Course, so item is empty
        ];

        $this->mockGql([
            ProductsWithCertificates::class => $this->gqlResponse([
                'site' => ['products' => $this->gqlEdgeConnection([$bundleProduct])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->products->productsWithCertificates()->items());

        $this->assertFalse($items[0]->hasCertificates);
        $this->assertEquals('BUNDLE', $items[0]->productable_type);
    }

    public function test_maps_productable_type(): void
    {
        $this->mockGql([
            ProductsWithCertificates::class => $this->gqlResponse([
                'site' => ['products' => $this->gqlEdgeConnection([$this->gqlProductWithCertificates()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->products->productsWithCertificates()->items());

        $this->assertEquals('COURSE', $items[0]->productable_type);
    }

    public function test_maps_productable_id_from_item(): void
    {
        $this->mockGql([
            ProductsWithCertificates::class => $this->gqlResponse([
                'site' => ['products' => $this->gqlEdgeConnection([$this->gqlProductWithCertificates()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->products->productsWithCertificates()->items());

        $this->assertEquals('101', $items[0]->productable_id);
    }

    public function test_terminates_at_last_page(): void
    {
        $this->mockGql([
            ProductsWithCertificates::class => $this->gqlResponse([
                'site' => ['products' => $this->gqlEdgeConnection(
                    [$this->gqlProductWithCertificates()], hasNextPage: false
                )],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->products->productsWithCertificates() as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    public function test_returns_empty_for_no_products(): void
    {
        $this->mockGql([
            ProductsWithCertificates::class => $this->gqlResponse([
                'site' => ['products' => $this->gqlEdgeConnection([])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->products->productsWithCertificates()->items());

        $this->assertEmpty($items);
    }

    public function test_multiple_products_mapped_correctly(): void
    {
        $p1 = $this->gqlProductWithCertificates(['id' => 'prod_1', 'name' => 'Course A', 'slug' => 'course-a']);
        $p2 = $this->gqlProductWithCertificates(['id' => 'prod_2', 'name' => 'Course B', 'slug' => 'course-b']);

        $this->mockGql([
            ProductsWithCertificates::class => $this->gqlResponse([
                'site' => ['products' => $this->gqlEdgeConnection([$p1, $p2])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->products->productsWithCertificates()->items());

        $this->assertCount(2, $items);
        $this->assertEquals('Course A', $items[0]->name);
        $this->assertEquals('Course B', $items[1]->name);
    }
}