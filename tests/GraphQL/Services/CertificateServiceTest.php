<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Certificates\Certificate;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Products\Product;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Certificates\CertificatesForCourse;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Certificates\CertificatesForUser;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

class CertificateServiceTest extends GraphQLTestCase
{
    // -------------------------------------------------------------------------
    // certificatesByUserEmail() — user hoisted to userByEmail level
    // -------------------------------------------------------------------------

    public function test_certificates_by_user_email_returns_certificate_dtos(): void
    {
        $certNode = $this->gqlCertificateNode();
        $userByEmail = array_merge(
            $this->gqlUserMinimal(),
            ['certificates' => $this->gqlConnection([$certNode])]
        );

        $this->mockGql([
            CertificatesForUser::class => $this->gqlResponse(['userByEmail' => $userByEmail]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByUserEmail('bob@example.com')->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Certificate::class, $items[0]);
        $this->assertEquals('cert_1', $items[0]->id);
        $this->assertEquals('CRED-001', $items[0]->credential_id);
        $this->assertEquals('https://example.com/cert.pdf', $items[0]->pdf_download_path);
    }

    public function test_certificates_by_user_email_user_is_hoisted_and_shared(): void
    {
        // After the hoist optimisation, user is read once from userByEmail level
        // and reused for every certificate — all certs share the same User instance.
        $cert1 = $this->gqlCertificateNode(['id' => 'cert_1']);
        $cert2 = $this->gqlCertificateNode(['id' => 'cert_2']);
        $userByEmail = array_merge(
            $this->gqlUserMinimal(['id' => 42, 'email' => 'alice@example.com']),
            ['certificates' => $this->gqlConnection([$cert1, $cert2])]
        );

        $this->mockGql([
            CertificatesForUser::class => $this->gqlResponse(['userByEmail' => $userByEmail]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByUserEmail('alice@example.com')->items());

        $this->assertCount(2, $items);
        $this->assertInstanceOf(User::class, $items[0]->user);
        $this->assertInstanceOf(User::class, $items[1]->user);
        // Both certs carry the queried user's identity
        $this->assertEquals(42, $items[0]->user->id);
        $this->assertEquals(42, $items[1]->user->id);
        $this->assertEquals('alice@example.com', $items[0]->user->email);
    }

    public function test_certificates_by_user_email_maps_course_and_product(): void
    {
        $certNode = $this->gqlCertificateNode([
            'course' => array_merge(
                $this->gqlCourseNode(['id' => '999', 'title' => 'Advanced PHP', 'slug' => 'advanced-php', 'name' => 'advanced-php']),
                ['product' => $this->gqlProductNode(['id' => 'prod_999', 'itemId' => '999', 'name' => 'Advanced PHP'])]
            ),
        ]);
        $userByEmail = array_merge(
            $this->gqlUserMinimal(),
            ['certificates' => $this->gqlConnection([$certNode])]
        );

        $this->mockGql([
            CertificatesForUser::class => $this->gqlResponse(['userByEmail' => $userByEmail]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByUserEmail('bob@example.com')->items());

        $this->assertInstanceOf(Course::class, $items[0]->course);
        $this->assertEquals(999, $items[0]->course->id);
        $this->assertEquals('Advanced PHP', $items[0]->course->title);

        $this->assertInstanceOf(Product::class, $items[0]->product);
        $this->assertEquals('prod_999', $items[0]->product->id);
        $this->assertEquals('Advanced PHP', $items[0]->product->name);
    }

    public function test_certificates_by_user_email_expiry_date_nullable(): void
    {
        $certNode = $this->gqlCertificateNode(['expiryDate' => null]);
        $userByEmail = array_merge(
            $this->gqlUserMinimal(),
            ['certificates' => $this->gqlConnection([$certNode])]
        );

        $this->mockGql([
            CertificatesForUser::class => $this->gqlResponse(['userByEmail' => $userByEmail]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByUserEmail('bob@example.com')->items());

        $this->assertNull($items[0]->expiry_date);
    }

    public function test_certificates_by_user_email_issued_at_is_carbon(): void
    {
        $this->mockGql([
            CertificatesForUser::class => $this->gqlResponse([
                'userByEmail' => array_merge(
                    $this->gqlUserMinimal(),
                    ['certificates' => $this->gqlConnection([$this->gqlCertificateNode()])]
                ),
            ]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByUserEmail('bob@example.com')->items());

        $this->assertInstanceOf(\Carbon\Carbon::class, $items[0]->issued_at);
        $this->assertEquals('2024-01-15', $items[0]->issued_at->toDateString());
    }

    public function test_certificates_by_user_email_returns_empty_for_unknown_user(): void
    {
        $this->mockGql([
            CertificatesForUser::class => $this->gqlResponse(['userByEmail' => null]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByUserEmail('nobody@example.com')->items());

        $this->assertEmpty($items);
    }

    public function test_certificates_by_user_email_returns_empty_when_no_nodes(): void
    {
        $userByEmail = array_merge(
            $this->gqlUserMinimal(),
            ['certificates' => $this->gqlConnection([])]
        );

        $this->mockGql([
            CertificatesForUser::class => $this->gqlResponse(['userByEmail' => $userByEmail]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByUserEmail('bob@example.com')->items());

        $this->assertEmpty($items);
    }

    public function test_certificates_by_user_email_pagination_terminates(): void
    {
        $userByEmail = array_merge(
            $this->gqlUserMinimal(),
            ['certificates' => $this->gqlConnection([$this->gqlCertificateNode()], hasNextPage: false)]
        );

        $this->mockGql([
            CertificatesForUser::class => $this->gqlResponse(['userByEmail' => $userByEmail]),
        ]);

        $pages = 0;
        foreach ($this->gql->certificates->certificatesByUserEmail('bob@example.com') as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    // -------------------------------------------------------------------------
    // certificatesByCourse() — course/product hoisted to course-node level
    // -------------------------------------------------------------------------

    public function test_certificates_for_course_returns_certificate_dtos(): void
    {
        $courseNode = $this->gqlCourseWithCertificates([$this->gqlCourseCertificateNode()]);

        $this->mockGql([
            CertificatesForCourse::class => $this->gqlResponse([
                'site' => [
                    'courses' => [
                        'nodes' => [$courseNode],
                        'pageInfo' => $this->gqlPageInfo(),
                    ],
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByCourse('Intro to PHP')->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Certificate::class, $items[0]);
        $this->assertEquals('cert_1', $items[0]->id);
    }

    public function test_certificates_for_course_course_is_hoisted_and_shared(): void
    {
        // After the hoist optimisation, course/product are read from the course-node
        // and reused for all certificates — both certs carry the same Course/Product.
        $cert1 = $this->gqlCourseCertificateNode(['id' => 'cert_1']);
        $cert2 = $this->gqlCourseCertificateNode(['id' => 'cert_2']);
        $courseNode = $this->gqlCourseWithCertificates([$cert1, $cert2]);

        $this->mockGql([
            CertificatesForCourse::class => $this->gqlResponse([
                'site' => ['courses' => ['nodes' => [$courseNode], 'pageInfo' => $this->gqlPageInfo()]],
            ]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByCourse('Intro to PHP')->items());

        $this->assertCount(2, $items);
        $this->assertInstanceOf(Course::class, $items[0]->course);
        $this->assertInstanceOf(Course::class, $items[1]->course);
        $this->assertEquals(101, $items[0]->course->id);
        $this->assertEquals(101, $items[1]->course->id);
        $this->assertEquals('prod_101', $items[0]->product->id);
        $this->assertEquals('prod_101', $items[1]->product->id);
    }

    public function test_certificates_for_course_user_is_per_node(): void
    {
        // Unlike CertificatesForUser, each cert here has its OWN user (different enrollees)
        $cert1 = $this->gqlCourseCertificateNode(['user' => $this->gqlUserMinimal(['id' => 1, 'email' => 'alice@example.com'])]);
        $cert2 = $this->gqlCourseCertificateNode(['user' => $this->gqlUserMinimal(['id' => 2, 'email' => 'bob@example.com'])]);
        $courseNode = $this->gqlCourseWithCertificates([$cert1, $cert2]);

        $this->mockGql([
            CertificatesForCourse::class => $this->gqlResponse([
                'site' => ['courses' => ['nodes' => [$courseNode], 'pageInfo' => $this->gqlPageInfo()]],
            ]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByCourse('Intro to PHP')->items());

        $this->assertEquals('alice@example.com', $items[0]->user->email);
        $this->assertEquals('bob@example.com', $items[1]->user->email);
    }

    public function test_certificates_for_course_returns_empty_when_course_not_found(): void
    {
        $this->mockGql([
            CertificatesForCourse::class => $this->gqlResponse([
                'site' => ['courses' => ['nodes' => [], 'pageInfo' => $this->gqlPageInfo()]],
            ]),
        ]);

        $items = iterator_to_array($this->gql->certificates->certificatesByCourse('No Such Course')->items());

        $this->assertEmpty($items);
    }
}