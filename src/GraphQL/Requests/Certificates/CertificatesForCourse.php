<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Certificates;

use Carbon\Carbon;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\HasRequestPagination;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use Saloon\PaginationPlugin\CursorPaginator;
use Saloon\PaginationPlugin\Paginator;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Certificates\Certificate;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Products\Product;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class CertificatesForCourse extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public ?string $after = null;

    public function __construct(
        private readonly string $courseName,
        private readonly int    $per_page = 50
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function createDtoFromResponse(Response $response): array
    {
        // Get the first course (we filter by name, so should only be one)
        $course = $response->json('data.site.courses.nodes.0');

        if (!$course || !isset($course['certificates']['nodes'])) {
            return [];
        }

        // We filter to a single course, so the course/product is constant across every
        // certificate. Build it once at the course-node level and reuse it per DTO.
        $courseDto = new Course(
            id: (int)$course['id'],
            title: $course['title'],
            name: $course['name'],
            slug: $course['slug'],
        );

        $productDto = new Product(
            id: $course['product']['id'],
            productable_id: $course['product']['productableId'],
            status: $course['product']['status'],
            slug: $course['product']['slug'],
            name: $course['product']['name']
        );

        return array_map(fn($certificate) => new Certificate(
            id: $certificate['id'],
            credential_id: $certificate['credentialId'],
            pdf_download_path: $certificate['pdfDownloadPath'],
            issued_at: Carbon::parse($certificate['issuedAt']),
            expiry_date: isset($certificate['expiryDate']) ? Carbon::parse($certificate['expiryDate']) : null,
            user: new User(
                id: $certificate['user']['id'],
                email: $certificate['user']['email'],
                gid: $certificate['user']['gid'],
                first_name: $certificate['user']['firstName'],
                last_name: $certificate['user']['lastName'],
                has_admin_role: null,
                custom_profile_fields: null,
            ),
            course: $courseDto,
            product: $productDto,
        ), $course['certificates']['nodes']);
    }

    protected function defaultBody(): array
    {
        $variables = [
            'certificatesFirst' => $this->per_page,
            'certificatesAfter' => $this->after,
            'filter' => ['name' => $this->courseName],
        ];

        return [
            'query' => 'query CourseCertificates($filter: CourseFilter, $certificatesFirst: Int, $certificatesAfter: String) {
  site {
    courses(filter: $filter, first: 1) {
      nodes {
        name
        id
        slug
        title
        product {
          id
          productableId
          status
          slug
          name
        }
        certificates(first: $certificatesFirst, after: $certificatesAfter) {
          nodes {
            issuedAt
            id
            pdfDownloadPath
            expiryDate
            credentialId
            user {
              id
              gid
              email
              firstName
              lastName
            }
          }
          pageInfo {
            hasNextPage
            endCursor
            hasPreviousPage
            startCursor
          }
          totalCount
        }
      }
      pageInfo {
        endCursor
        hasNextPage
        hasPreviousPage
        startCursor
      }
    }
  }
}',
            'variables' => $variables
        ];
    }

    public function paginate(Connector $connector): Paginator
    {
        return new class(connector: $connector, request: $this) extends CursorPaginator {
            protected function getNextCursor(Response $response): int|string
            {
                return $response->json('data.site.courses.nodes.0.certificates.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return !$response->json('data.site.courses.nodes.0.certificates.pageInfo.hasNextPage');
            }


            protected function getPageItems(Response $response, Request $request): array
            {
                return $response->dto();
            }

            protected function applyPagination(Request $request): Request
            {
                if (is_null($this->currentResponse)) {
                    return $request;
                }

                $request->after = $this->getNextCursor($this->currentResponse);

                return $request;
            }
        };
    }
}