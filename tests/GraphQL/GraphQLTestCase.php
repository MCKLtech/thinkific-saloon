<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL;

use Mockery;
use PHPUnit\Framework\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\GraphQL\Services\ThinkificGraphQLService;

/**
 * Base class for all GraphQL endpoint tests.
 *
 * Provides factory methods that return arrays matching the exact field names and
 * nesting the GraphQL queries produce (camelCase keys, connection wrappers, etc).
 * Tests compose these via overrides rather than repeating fixture data inline.
 */
abstract class GraphQLTestCase extends TestCase
{
    protected ThinkificGraphQLService $gql;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gql = new ThinkificGraphQLService('test_oauth_token');
    }

    protected function tearDown(): void
    {
        MockClient::destroyGlobal();
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Response envelope helpers
    // -------------------------------------------------------------------------

    /**
     * Wrap a data payload in the standard GraphQL response envelope with a
     * healthy rate-limit extension block.
     */
    protected function gqlResponse(array $data, int $cost = 10, int $remaining = 1990): MockResponse
    {
        return MockResponse::make(
            body: json_encode([
                'data'       => $data,
                'extensions' => [
                    'rateLimit' => [
                        'cost'      => $cost,
                        'remaining' => $remaining,
                        'resetAt'   => '2026-06-02T00:01:00.000Z',
                        'limit'     => 2000,
                    ],
                ],
            ]),
            status: 200,
            headers: ['Content-Type' => 'application/json']
        );
    }

    /** GraphQL rate-limit error (HTTP 200 with errors array). */
    protected function gqlRateLimited(): MockResponse
    {
        return MockResponse::make(
            body: json_encode([
                'errors' => [[
                    'message'    => 'API rate limit exceeded.',
                    'extensions' => ['code' => 'RATE_LIMITED'],
                ]],
                'extensions' => [
                    'rateLimit' => [
                        'cost'      => 202,
                        'remaining' => 0,
                        'resetAt'   => '2026-06-02T00:02:00.000Z',
                        'limit'     => 2000,
                    ],
                ],
            ]),
            status: 200,
            headers: ['Content-Type' => 'application/json']
        );
    }

    /** Standard cursor pageInfo block. */
    protected function gqlPageInfo(bool $hasNextPage = false, ?string $endCursor = null): array
    {
        return [
            'hasNextPage'     => $hasNextPage,
            'hasPreviousPage' => false,
            'endCursor'       => $hasNextPage ? ($endCursor ?? 'cursor_page2') : null,
            'startCursor'     => 'cursor_start',
        ];
    }

    /** Wrap nodes in a connection with pageInfo. */
    protected function gqlConnection(array $nodes, bool $hasNextPage = false, ?string $endCursor = null): array
    {
        return [
            'nodes'    => $nodes,
            'pageInfo' => $this->gqlPageInfo($hasNextPage, $endCursor),
        ];
    }

    /** Wrap nodes as edges (node-wrapped) with pageInfo. */
    protected function gqlEdgeConnection(array $nodes, bool $hasNextPage = false, ?string $endCursor = null): array
    {
        return [
            'edges'    => array_map(fn($n) => ['node' => $n], $nodes),
            'pageInfo' => $this->gqlPageInfo($hasNextPage, $endCursor),
        ];
    }

    // -------------------------------------------------------------------------
    // Entity factories — match exact GraphQL field names from each query
    // -------------------------------------------------------------------------

    /**
     * A single User as returned by the Get / GetByEmail queries (full user root).
     * Includes customProfileFields edges and profile.avatar.
     */
    protected function gqlUserFull(array $overrides = []): array
    {
        return array_merge([
            'id'                  => 1,
            'gid'                 => 'gid://thinkific/User/1',
            'email'               => 'bob@example.com',
            'firstName'           => 'Bob',
            'lastName'            => 'Smith',
            'hasAdminRole'        => false,
            'customProfileFields' => [
                'edges' => [[
                    'cursor' => 'cpf_cursor_1',
                    'node'   => [
                        'id'       => 'cpf_1',
                        'label'    => 'Phone',
                        'required' => false,
                        'type'     => 'TEXT',
                        'typeId'   => 'cpf_def_1',
                        'value'    => '555-1234',
                    ],
                ]],
            ],
            'profile' => [
                'avatar' => [
                    'altText' => 'Bob Smith',
                    'url'     => 'https://cdn.thinkific.com/avatars/bob.jpg',
                ],
            ],
        ], $overrides);
    }

    /**
     * A User node as returned inside paginated site.users.edges (same fields
     * but accessed via $user['node']).
     */
    protected function gqlUserNode(array $overrides = []): array
    {
        return $this->gqlUserFull($overrides);
    }

    /**
     * A minimal User as returned on per-node certificate user fields.
     */
    protected function gqlUserMinimal(array $overrides = []): array
    {
        return array_merge([
            'id'        => 1,
            'gid'       => 'gid://thinkific/User/1',
            'email'     => 'bob@example.com',
            'firstName' => 'Bob',
            'lastName'  => 'Smith',
        ], $overrides);
    }

    protected function gqlCourseNode(array $overrides = []): array
    {
        return array_merge([
            'id'    => '101',
            'name'  => 'intro-to-php',
            'slug'  => 'intro-to-php',
            'title' => 'Intro to PHP',
        ], $overrides);
    }

    protected function gqlProductNode(array $overrides = []): array
    {
        return array_merge([
            'id'            => 'prod_101',
            'itemId'        => '101',
            'status'        => 'PUBLISHED',
            'slug'          => 'intro-to-php',
            'name'          => 'Intro to PHP',
        ], $overrides);
    }

    protected function gqlGroupNode(array $overrides = []): array
    {
        return array_merge([
            'id'        => 201,
            'name'      => 'Cohort A',
            'createdAt' => '2024-01-01T00:00:00Z',
        ], $overrides);
    }

    /**
     * A CertificateRecord node as it appears inside userByEmail.certificates.nodes
     * (after the user-hoist optimisation — no 'user' key here).
     */
    protected function gqlCertificateNode(array $overrides = []): array
    {
        return array_merge([
            'id'              => 'cert_1',
            'issuedId'        => 'CRED-001',
            'pdfDownloadPath' => 'https://example.com/cert.pdf',
            'issuedAt'        => '2024-01-15T10:00:00Z',
            'expiryDate'      => '2025-01-15T10:00:00Z',
            'course'          => array_merge(
                $this->gqlCourseNode(),
                ['product' => $this->gqlProductNode()]
            ),
        ], $overrides);
    }

    /**
     * A CertificateRecord as it appears inside site.courses.nodes[0].certificates.nodes
     * (course-hoist optimisation — no 'course' key here, user IS per-node).
     */
    protected function gqlCourseCertificateNode(array $overrides = []): array
    {
        return array_merge([
            'id'              => 'cert_1',
            'issuedId'        => 'CRED-001',
            'pdfDownloadPath' => 'https://example.com/cert.pdf',
            'issuedAt'        => '2024-01-15T10:00:00Z',
            'expiryDate'      => '2025-01-15T10:00:00Z',
            'user'            => $this->gqlUserMinimal(),
        ], $overrides);
    }

    /**
     * A site.courses.nodes[0] node as returned by CertificatesForCourse.
     * Course/product are at this level (hoisted); certificates are nested.
     */
    protected function gqlCourseWithCertificates(array $certNodes = [], bool $hasNextPage = false): array
    {
        return array_merge(
            $this->gqlCourseNode(),
            [
                'product'      => $this->gqlProductNode(),
                'certificates' => $this->gqlConnection($certNodes, $hasNextPage),
            ]
        );
    }

    protected function gqlSubmissionNode(array $overrides = []): array
    {
        return array_merge([
            'id'         => 301,
            'status'     => 'PENDING',
            'file'       => [
                'name' => 'submission.pdf',
                'type' => 'application/pdf',
                'url'  => 'https://example.com/files/submission.pdf',
            ],
            'reviewedAt' => null,
            'updatedAt'  => '2024-01-15T10:00:00Z',
            'createdAt'  => '2024-01-14T09:00:00Z',
            'assignment' => [
                'lesson' => [
                    'id'      => 401,
                    'title'   => 'Lesson 1: Introduction',
                    'chapter' => ['id' => 10, 'title' => 'Chapter 1'],
                    'course'  => ['product' => ['id' => 1, 'name' => 'Intro to PHP']],
                ],
            ],
        ], $overrides);
    }

    /** Submission node with an inline user (site-level Assignments query). */
    protected function gqlSubmissionNodeWithUser(array $overrides = []): array
    {
        return array_merge($this->gqlSubmissionNode(), [
            'user' => [
                'id'        => 1,
                'gid'       => 'gid://thinkific/User/1',
                'email'     => 'bob@example.com',
                'firstName' => 'Bob',
                'lastName'  => 'Smith',
            ],
        ], $overrides);
    }

    protected function gqlChapterNode(array $overrides = []): array
    {
        return array_merge([
            'id'       => 10,
            'position' => 1,
            'title'    => 'Chapter 1',
            'lessons'  => [
                'nodes' => [[
                    'id'         => 401,
                    'lessonType' => 'video',
                    'title'      => 'Lesson 1',
                    'takeUrl'    => 'https://example.com/take/401',
                    'content'    => ['id' => 501, 'contentType' => 'video'],
                ]],
            ],
        ], $overrides);
    }

    protected function gqlSurveyNode(array $overrides = []): array
    {
        return array_merge([
            'id'        => 601,
            'createdAt' => '2024-01-01T00:00:00Z',
            'questions' => [
                'nodes' => [[
                    'id'           => 701,
                    'questionType' => 'MULTIPLE_CHOICE',
                    'position'     => 1,
                    'prompt'       => 'How satisfied are you?',
                    'choices'      => [
                        'nodes' => [
                            ['id' => 801, 'text' => 'Very satisfied', 'position' => 1],
                            ['id' => 802, 'text' => 'Satisfied',      'position' => 2],
                        ],
                    ],
                ]],
            ],
        ], $overrides);
    }

    protected function gqlSurveyResponseNode(array $overrides = []): array
    {
        return array_merge([
            'id'          => 901,
            'createdAt'   => '2024-02-01T10:00:00Z',
            'completedAt' => '2024-02-01T10:05:00Z',
            'survey'      => ['id' => 601],
            'user'        => $this->gqlUserMinimal(),
            'userAnswers' => [
                'nodes' => [[
                    'textResponse' => null,
                    'skipped'      => false,
                    'question'     => ['id' => 701],
                    'choices'      => [
                        ['id' => 801, 'text' => 'Very satisfied'],
                    ],
                ]],
            ],
        ], $overrides);
    }

    protected function gqlProductWithCertificates(array $overrides = []): array
    {
        return array_merge([
            'id'             => 'prod_101',
            'name'           => 'Intro to PHP',
            'slug'           => 'intro-to-php',
            'status'         => 'PUBLISHED',
            'itemType'       => 'COURSE',
            'item'           => [
                'id'           => '101',
                'name'         => 'intro-to-php',
                'slug'         => 'intro-to-php',
                'certificates' => ['totalCount' => 3],
            ],
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Mock registration helper
    // -------------------------------------------------------------------------

    protected function mockGql(array $map): void
    {
        MockClient::global($map);
    }
}