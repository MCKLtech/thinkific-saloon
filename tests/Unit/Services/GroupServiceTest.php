<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Groups\Group;
use WooNinja\ThinkificSaloon\Requests\Groups\AddUser;
use WooNinja\ThinkificSaloon\Requests\Groups\Analysts;
use WooNinja\ThinkificSaloon\Requests\Groups\AssignAnalysts;
use WooNinja\ThinkificSaloon\Requests\Groups\Create;
use WooNinja\ThinkificSaloon\Requests\Groups\Delete;
use WooNinja\ThinkificSaloon\Requests\Groups\Get;
use WooNinja\ThinkificSaloon\Requests\Groups\Groups;
use WooNinja\ThinkificSaloon\Requests\Groups\RemoveAnalyst;
use WooNinja\ThinkificSaloon\Requests\Users\Users;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class GroupServiceTest extends TestCase
{
    public function test_can_get_group_by_id(): void
    {
        // Get/Create nest the group under a "group" key, unlike list endpoints.
        $this->mockGlobalRequests([
            Get::class => MockResponse::make(['group' => $this->mockGroupData(['id' => 4])], 200),
        ]);

        $group = $this->service->groups->get(4);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals(4, $group->id);
    }

    public function test_can_list_groups(): void
    {
        $groups = [
            $this->mockGroupData(['id' => 1]),
            $this->mockGroupData(['id' => 2]),
        ];

        $this->mockGlobalRequests([
            Groups::class => MockResponse::make([
                'items' => $groups,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->groups->groups()->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Group::class, $result[0]);
    }

    public function test_can_create_group(): void
    {
        $this->mockGlobalRequests([
            Create::class => MockResponse::make(['group' => $this->mockGroupData(['id' => 9, 'name' => 'New Group'])], 201),
        ]);

        $group = $this->service->groups->create('New Group');

        $this->assertEquals(9, $group->id);
        $this->assertEquals('New Group', $group->name);
    }

    public function test_can_delete_group(): void
    {
        $this->mockGlobalRequests([
            Delete::class => MockResponse::make([], 200),
        ]);

        $this->service->groups->delete(4);

        $this->addToAssertionCount(1);
    }

    public function test_can_add_user_to_groups(): void
    {
        $this->mockGlobalRequests([
            AddUser::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->groups->addUser(1, ['VIP', 'Beta']);

        $this->assertTrue($response->successful());
    }

    public function test_is_user_in_group_returns_true_when_a_match_is_found(): void
    {
        $this->mockGlobalRequests([
            Users::class => MockResponse::make([
                'items' => [$this->mockUserData(['id' => 1])],
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 1, 'entries_info' => '1-1 of 1',
                ]],
            ], 200),
        ]);

        $this->assertTrue($this->service->groups->isUserInGroup('bob@example.com', 4));
    }

    public function test_is_user_in_group_returns_false_when_no_match_is_found(): void
    {
        $this->mockGlobalRequests([
            Users::class => MockResponse::make([
                'items' => [],
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 0, 'entries_info' => '0 of 0',
                ]],
            ], 200),
        ]);

        $this->assertFalse($this->service->groups->isUserInGroup('nobody@example.com', 4));
    }

    public function test_can_get_group_analysts(): void
    {
        $this->mockGlobalRequests([
            Analysts::class => MockResponse::make(['group_analysts' => [['id' => 1], ['id' => 2]]], 200),
        ]);

        $analysts = $this->service->groups->analysts(4);

        $this->assertCount(2, $analysts);
    }

    public function test_can_assign_analysts_to_group(): void
    {
        $this->mockGlobalRequests([
            AssignAnalysts::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->groups->assignAnalysts(4, [1, 2]);

        $this->assertTrue($response->successful());
    }

    public function test_can_remove_analyst_from_group(): void
    {
        $this->mockGlobalRequests([
            RemoveAnalyst::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->groups->removeAnalyst(4, 1);

        $this->assertTrue($response->successful());
    }
}
