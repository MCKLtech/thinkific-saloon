<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Groups\Group;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\Avatar;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Get;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\GetByEmail;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Groups;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Users;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

class UserServiceTest extends GraphQLTestCase
{
    // -------------------------------------------------------------------------
    // get() — user by GID
    // -------------------------------------------------------------------------

    public function test_get_by_gid_returns_user_dto(): void
    {
        $this->mockGql([
            Get::class => $this->gqlResponse(['user' => $this->gqlUserFull()]),
        ]);

        $user = $this->gql->users->get('gid://thinkific/User/1');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('gid://thinkific/User/1', $user->gid);
        $this->assertEquals('bob@example.com', $user->email);
        $this->assertEquals('Bob', $user->first_name);
        $this->assertEquals('Smith', $user->last_name);
        $this->assertFalse($user->has_admin_role);
    }

    public function test_get_by_gid_maps_avatar(): void
    {
        $this->mockGql([
            Get::class => $this->gqlResponse(['user' => $this->gqlUserFull()]),
        ]);

        $user = $this->gql->users->get('gid://thinkific/User/1');

        $this->assertInstanceOf(Avatar::class, $user->avatar);
        $this->assertEquals('https://cdn.thinkific.com/avatars/bob.jpg', $user->avatar->url);
        $this->assertEquals('Bob Smith', $user->avatar->alt_text);
    }

    public function test_get_by_gid_avatar_is_null_when_absent(): void
    {
        $userData = $this->gqlUserFull(['profile' => ['avatar' => null]]);
        $this->mockGql([
            Get::class => $this->gqlResponse(['user' => $userData]),
        ]);

        $user = $this->gql->users->get('gid://thinkific/User/1');

        $this->assertNull($user->avatar);
    }

    public function test_get_by_gid_maps_custom_profile_fields(): void
    {
        $this->mockGql([
            Get::class => $this->gqlResponse(['user' => $this->gqlUserFull()]),
        ]);

        $user = $this->gql->users->get('gid://thinkific/User/1');

        $this->assertIsArray($user->custom_profile_fields);
        $this->assertArrayHasKey('edges', $user->custom_profile_fields);
        $this->assertEquals('Phone', $user->custom_profile_fields['edges'][0]['node']['label']);
        $this->assertEquals('555-1234', $user->custom_profile_fields['edges'][0]['node']['value']);
    }

    // -------------------------------------------------------------------------
    // getByEmail()
    // -------------------------------------------------------------------------

    public function test_get_by_email_returns_user_dto(): void
    {
        $this->mockGql([
            GetByEmail::class => $this->gqlResponse(['userByEmail' => $this->gqlUserFull()]),
        ]);

        $user = $this->gql->users->getByEmail('bob@example.com');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('bob@example.com', $user->email);
        $this->assertEquals('Bob', $user->first_name);
    }

    public function test_get_by_email_maps_avatar(): void
    {
        $this->mockGql([
            GetByEmail::class => $this->gqlResponse(['userByEmail' => $this->gqlUserFull()]),
        ]);

        $user = $this->gql->users->getByEmail('bob@example.com');

        $this->assertInstanceOf(Avatar::class, $user->avatar);
        $this->assertEquals('https://cdn.thinkific.com/avatars/bob.jpg', $user->avatar->url);
    }

    public function test_get_by_email_avatar_null_when_absent(): void
    {
        $userData = $this->gqlUserFull();
        unset($userData['profile']);
        $this->mockGql([
            GetByEmail::class => $this->gqlResponse(['userByEmail' => $userData]),
        ]);

        $user = $this->gql->users->getByEmail('bob@example.com');

        $this->assertNull($user->avatar);
    }

    // -------------------------------------------------------------------------
    // users() — paginated list
    // -------------------------------------------------------------------------

    public function test_users_list_maps_edge_nodes_to_dtos(): void
    {
        $node1 = $this->gqlUserNode(['id' => 1, 'email' => 'alice@example.com', 'firstName' => 'Alice']);
        $node2 = $this->gqlUserNode(['id' => 2, 'email' => 'bob@example.com',   'firstName' => 'Bob']);

        $this->mockGql([
            Users::class => $this->gqlResponse([
                'site' => [
                    'users' => $this->gqlEdgeConnection([$node1, $node2]),
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->users->users()->items());

        $this->assertCount(2, $items);
        $this->assertInstanceOf(User::class, $items[0]);
        $this->assertEquals('alice@example.com', $items[0]->email);
        $this->assertEquals('bob@example.com', $items[1]->email);
    }

    public function test_users_list_maps_avatar_on_each_node(): void
    {
        $node = $this->gqlUserNode();

        $this->mockGql([
            Users::class => $this->gqlResponse([
                'site' => ['users' => $this->gqlEdgeConnection([$node])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->users->users()->items());

        $this->assertInstanceOf(Avatar::class, $items[0]->avatar);
        $this->assertEquals('https://cdn.thinkific.com/avatars/bob.jpg', $items[0]->avatar->url);
    }

    public function test_users_list_terminates_at_last_page(): void
    {
        $this->mockGql([
            Users::class => $this->gqlResponse([
                'site' => [
                    'users' => $this->gqlEdgeConnection([$this->gqlUserNode()], hasNextPage: false),
                ],
            ]),
        ]);

        $paginator = $this->gql->users->users();
        $pages = 0;
        foreach ($paginator as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    public function test_users_list_returns_empty_for_no_results(): void
    {
        $this->mockGql([
            Users::class => $this->gqlResponse([
                'site' => ['users' => $this->gqlEdgeConnection([])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->users->users()->items());

        $this->assertEmpty($items);
    }

    // -------------------------------------------------------------------------
    // groups() — by GID and by email
    // -------------------------------------------------------------------------

    public function test_groups_by_gid_returns_group_dtos(): void
    {
        $group = $this->gqlGroupNode();
        $this->mockGql([
            Groups::class => $this->gqlResponse([
                'user' => ['groups' => ['nodes' => [$group]]],
            ]),
        ]);

        $groups = $this->gql->users->groups('gid://thinkific/User/1');

        $this->assertCount(1, $groups);
        $this->assertInstanceOf(Group::class, $groups[0]);
        $this->assertEquals(201, $groups[0]->id);
        $this->assertEquals('Cohort A', $groups[0]->name);
    }

    public function test_groups_by_email_returns_group_dtos(): void
    {
        $group = $this->gqlGroupNode(['id' => 202, 'name' => 'Cohort B']);
        $this->mockGql([
            Groups::class => $this->gqlResponse([
                'userByEmail' => ['groups' => ['nodes' => [$group]]],
            ]),
        ]);

        $groups = $this->gql->users->groups('bob@example.com');

        $this->assertCount(1, $groups);
        $this->assertEquals('Cohort B', $groups[0]->name);
    }

    public function test_groups_returns_empty_array_when_no_groups(): void
    {
        $this->mockGql([
            Groups::class => $this->gqlResponse([
                'user' => ['groups' => ['nodes' => []]],
            ]),
        ]);

        $groups = $this->gql->users->groups('gid://thinkific/User/1');

        $this->assertEmpty($groups);
    }
}