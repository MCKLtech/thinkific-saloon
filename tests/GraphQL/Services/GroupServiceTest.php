<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Groups\Group;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Groups\Groups;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Groups\Users;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

class GroupServiceTest extends GraphQLTestCase
{
    // -------------------------------------------------------------------------
    // groups() — paginated site group list
    // -------------------------------------------------------------------------

    public function test_groups_list_returns_group_dtos(): void
    {
        $group1 = $this->gqlGroupNode(['id' => 201, 'name' => 'Cohort A']);
        $group2 = $this->gqlGroupNode(['id' => 202, 'name' => 'Cohort B']);

        $this->mockGql([
            Groups::class => $this->gqlResponse([
                'site' => ['groups' => $this->gqlConnection([$group1, $group2])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->groups->groups()->items());

        $this->assertCount(2, $items);
        $this->assertInstanceOf(Group::class, $items[0]);
        $this->assertEquals(201, $items[0]->id);
        $this->assertEquals('Cohort A', $items[0]->name);
        $this->assertEquals(202, $items[1]->id);
        $this->assertEquals('Cohort B', $items[1]->name);
    }

    public function test_groups_list_maps_created_at_as_carbon(): void
    {
        $this->mockGql([
            Groups::class => $this->gqlResponse([
                'site' => ['groups' => $this->gqlConnection([$this->gqlGroupNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->groups->groups()->items());

        $this->assertInstanceOf(\Carbon\Carbon::class, $items[0]->created_at);
        $this->assertEquals('2024-01-01', $items[0]->created_at->toDateString());
    }

    public function test_groups_list_terminates_at_last_page(): void
    {
        $this->mockGql([
            Groups::class => $this->gqlResponse([
                'site' => ['groups' => $this->gqlConnection([$this->gqlGroupNode()], hasNextPage: false)],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->groups->groups() as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    public function test_groups_list_returns_empty_when_no_groups(): void
    {
        $this->mockGql([
            Groups::class => $this->gqlResponse([
                'site' => ['groups' => $this->gqlConnection([])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->groups->groups()->items());

        $this->assertEmpty($items);
    }

    // -------------------------------------------------------------------------
    // users() — paginated users within a group
    // -------------------------------------------------------------------------

    public function test_group_users_returns_user_dtos(): void
    {
        $user1 = $this->gqlUserNode(['id' => 1, 'email' => 'alice@example.com', 'firstName' => 'Alice']);
        $user2 = $this->gqlUserNode(['id' => 2, 'email' => 'bob@example.com',   'firstName' => 'Bob']);

        $this->mockGql([
            Users::class => $this->gqlResponse([
                'group' => [
                    'id'        => 201,
                    'name'      => 'Cohort A',
                    'createdAt' => '2024-01-01T00:00:00Z',
                    'users'     => $this->gqlEdgeConnection([$user1, $user2]),
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->groups->users(201)->items());

        $this->assertCount(2, $items);
        $this->assertInstanceOf(User::class, $items[0]);
        $this->assertEquals('alice@example.com', $items[0]->email);
        $this->assertEquals('Alice', $items[0]->first_name);
        $this->assertEquals('bob@example.com', $items[1]->email);
    }

    public function test_group_users_maps_gid(): void
    {
        $user = $this->gqlUserNode(['gid' => 'gid://thinkific/User/99']);

        $this->mockGql([
            Users::class => $this->gqlResponse([
                'group' => [
                    'id' => 201, 'name' => 'Cohort A', 'createdAt' => '2024-01-01T00:00:00Z',
                    'users' => $this->gqlEdgeConnection([$user]),
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->groups->users(201)->items());

        $this->assertEquals('gid://thinkific/User/99', $items[0]->gid);
    }

    public function test_group_users_terminates_at_last_page(): void
    {
        $this->mockGql([
            Users::class => $this->gqlResponse([
                'group' => [
                    'id' => 201, 'name' => 'Cohort A', 'createdAt' => '2024-01-01T00:00:00Z',
                    'users' => $this->gqlEdgeConnection([$this->gqlUserNode()], hasNextPage: false),
                ],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->groups->users(201) as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    public function test_group_users_returns_empty_when_no_members(): void
    {
        $this->mockGql([
            Users::class => $this->gqlResponse([
                'group' => [
                    'id' => 201, 'name' => 'Empty Group', 'createdAt' => '2024-01-01T00:00:00Z',
                    'users' => $this->gqlEdgeConnection([]),
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->groups->users(201)->items());

        $this->assertEmpty($items);
    }
}