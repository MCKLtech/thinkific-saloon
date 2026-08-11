<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use Carbon\Carbon;
use Saloon\Http\Faking\MockClient;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments\Enums\AssignmentStatus;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments\Submission;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments\UpdateAssignment;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Assignments\Assignments;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Assignments\Update;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Assignments as UserAssignments;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

class AssignmentServiceTest extends GraphQLTestCase
{
    // -------------------------------------------------------------------------
    // assignments() — site-level, with optional user filter
    // -------------------------------------------------------------------------

    public function test_site_assignments_returns_submission_dtos(): void
    {
        $this->mockGql([
            Assignments::class => $this->gqlResponse([
                'site' => [
                    'assignmentSubmissions' => $this->gqlEdgeConnection([
                        $this->gqlSubmissionNodeWithUser(),
                    ]),
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->assignments->assignments()->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Submission::class, $items[0]);
        $this->assertEquals(301, $items[0]->id);
        $this->assertEquals('PENDING', $items[0]->status);
    }

    public function test_site_assignments_maps_file_fields(): void
    {
        $this->mockGql([
            Assignments::class => $this->gqlResponse([
                'site' => [
                    'assignmentSubmissions' => $this->gqlEdgeConnection([
                        $this->gqlSubmissionNodeWithUser(),
                    ]),
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->assignments->assignments()->items());

        $this->assertEquals('submission.pdf', $items[0]->name);
        $this->assertEquals('application/pdf', $items[0]->type);
        $this->assertEquals('https://example.com/files/submission.pdf', $items[0]->url);
    }

    public function test_site_assignments_maps_lesson_and_chapter(): void
    {
        $this->mockGql([
            Assignments::class => $this->gqlResponse([
                'site' => [
                    'assignmentSubmissions' => $this->gqlEdgeConnection([
                        $this->gqlSubmissionNodeWithUser(),
                    ]),
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->assignments->assignments()->items());

        $this->assertEquals(401, $items[0]->lesson_id);
        $this->assertEquals('Lesson 1: Introduction', $items[0]->lesson_name);
        $this->assertEquals(10, $items[0]->chapter_id);
        $this->assertEquals('Chapter 1', $items[0]->chapter_name);
        $this->assertEquals(1, $items[0]->product_id);
        $this->assertEquals('Intro to PHP', $items[0]->product_name);
    }

    public function test_site_assignments_maps_per_node_user(): void
    {
        $node = $this->gqlSubmissionNodeWithUser([
            'user' => [
                'id'        => 42,
                'gid'       => 'gid://thinkific/User/42',
                'email'     => 'charlie@example.com',
                'firstName' => 'Charlie',
                'lastName'  => 'Brown',
            ],
        ]);

        $this->mockGql([
            Assignments::class => $this->gqlResponse([
                'site' => ['assignmentSubmissions' => $this->gqlEdgeConnection([$node])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->assignments->assignments()->items());

        $this->assertInstanceOf(User::class, $items[0]->user);
        $this->assertEquals(42, $items[0]->user->id);
        $this->assertEquals('charlie@example.com', $items[0]->user->email);
    }

    public function test_site_assignments_reviewed_at_is_nullable(): void
    {
        $this->mockGql([
            Assignments::class => $this->gqlResponse([
                'site' => ['assignmentSubmissions' => $this->gqlEdgeConnection([
                    $this->gqlSubmissionNodeWithUser(['reviewedAt' => null]),
                ])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->assignments->assignments()->items());

        $this->assertNull($items[0]->reviewed_at);
    }

    public function test_site_assignments_reviewed_at_is_carbon_when_set(): void
    {
        $node = $this->gqlSubmissionNodeWithUser(['reviewedAt' => '2024-02-01T12:00:00Z']);

        $this->mockGql([
            Assignments::class => $this->gqlResponse([
                'site' => ['assignmentSubmissions' => $this->gqlEdgeConnection([$node])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->assignments->assignments()->items());

        $this->assertInstanceOf(Carbon::class, $items[0]->reviewed_at);
        $this->assertEquals('2024-02-01', $items[0]->reviewed_at->toDateString());
    }

    public function test_site_assignments_terminates_at_last_page(): void
    {
        $this->mockGql([
            Assignments::class => $this->gqlResponse([
                'site' => ['assignmentSubmissions' => $this->gqlEdgeConnection(
                    [$this->gqlSubmissionNodeWithUser()], hasNextPage: false
                )],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->assignments->assignments() as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    public function test_site_assignments_returns_empty_for_no_results(): void
    {
        $this->mockGql([
            Assignments::class => $this->gqlResponse([
                'site' => ['assignmentSubmissions' => $this->gqlEdgeConnection([])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->assignments->assignments()->items());

        $this->assertEmpty($items);
    }

    // -------------------------------------------------------------------------
    // users->assignments() — user-scoped submissions (hoisted user)
    //
    // NOTE: Users/Assignments.php omits lesson_id and lesson_name from the
    // Submission constructor call. Since those are required (non-nullable) on
    // the Submission DTO, these tests will fail with an ArgumentCountError until
    // the mapping is corrected to pass lesson_id and lesson_name.
    // -------------------------------------------------------------------------

    public function test_user_assignments_user_is_hoisted_from_parent(): void
    {
        $userNode = array_merge($this->gqlUserMinimal(['id' => 99, 'email' => 'dana@example.com']), [
            'hasAdminRole'        => false,
            'customProfileFields' => null,
            'assignmentSubmissions' => $this->gqlEdgeConnection([
                $this->gqlSubmissionNode(),
            ]),
        ]);

        $this->mockGql([
            UserAssignments::class => $this->gqlResponse(['user' => $userNode]),
        ]);

        $items = iterator_to_array($this->gql->users->assignments('gid://thinkific/User/99')->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(User::class, $items[0]->user);
        // User is read from data.user, not from each submission node
        $this->assertEquals(99, $items[0]->user->id);
        $this->assertEquals('dana@example.com', $items[0]->user->email);
    }

    public function test_user_assignments_terminates_at_last_page(): void
    {
        $userNode = array_merge($this->gqlUserMinimal(), [
            'hasAdminRole'          => false,
            'customProfileFields'   => null,
            'assignmentSubmissions' => $this->gqlEdgeConnection(
                [$this->gqlSubmissionNode()], hasNextPage: false
            ),
        ]);

        $this->mockGql([
            UserAssignments::class => $this->gqlResponse(['user' => $userNode]),
        ]);

        $pages = 0;
        foreach ($this->gql->users->assignments('gid://thinkific/User/1') as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    // -------------------------------------------------------------------------
    // update() — updateAssignmentSubmissionStatus mutation
    // -------------------------------------------------------------------------

    public function test_update_sends_the_mutation(): void
    {
        $this->mockGql([
            Update::class => $this->gqlResponse([
                'updateAssignmentSubmissionStatus' => [
                    'submission' => [
                        'id'         => 301,
                        'status'     => 'APPROVED',
                        'createdAt'  => '2024-01-14T09:00:00Z',
                        'updatedAt'  => '2024-02-01T12:00:00Z',
                        'reviewedAt' => '2024-02-01T12:00:00Z',
                    ],
                ],
            ]),
        ]);

        $this->gql->assignments->update(new UpdateAssignment(301, AssignmentStatus::APPROVED));

        MockClient::getGlobal()->assertSent(Update::class);
    }
}