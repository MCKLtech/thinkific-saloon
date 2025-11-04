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

final class CertificatesForUser extends Request implements HasBody, HasRequestPagination, Paginatable
{
    protected Method $method = Method::POST;

    use HasJsonBody;

    public ?string $after = null;

    public function __construct(
        private readonly string $email,
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
        $user = $response->json('data.userByEmail');

        if (!$user || !isset($user['certificates']['nodes'])) {
            return [];
        }

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
            ),
            course: new Course(
                id: (int) $certificate['course']['id'],
                title: $certificate['course']['title'],
                name: $certificate['course']['name'],
                slug: $certificate['course']['slug'],
            ),
            product: new Product(
                id: $certificate['course']['product']['id'],
                productable_id: $certificate['course']['product']['productableId'],
                status: $certificate['course']['product']['status'],
                slug: $certificate['course']['product']['slug'],
                name: $certificate['course']['product']['name']
            ),
        ), $user['certificates']['nodes']);
    }

    protected function defaultBody(): array
    {
        $variables = [
            'email' => $this->email,
            'first' => $this->per_page,
            'after' => $this->after,
        ];

        return [
            'query' => 'query Certificates($email: EmailAddress!, $first: Int, $after: String) {
  userByEmail(email: $email) {
    certificates(first: $first, after: $after) {
      nodes {
        credentialId
        expiryDate
        id
        issuedAt
        pdfDownloadPath
        user {
          id
          firstName
          lastName
          email
          gid
        }
        course {
          id
          slug
          title
          name
          product {
            id
            productableId
            status
            slug
            name
          }
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
                return $response->json('data.userByEmail.certificates.pageInfo.endCursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return !$response->json('data.userByEmail.certificates.pageInfo.hasNextPage');
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