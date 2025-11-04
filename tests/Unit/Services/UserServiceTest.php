<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\Requests\Users\Get;
use WooNinja\ThinkificSaloon\Requests\Users\Users;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class UserServiceTest extends TestCase
{
    public function test_can_get_user_by_id(): void
    {
        // Arrange - Set up Global MockClient
        $userData = $this->mockUserData(['id' => 123]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($userData, 200)
        ]);

        // Act
        $user = $this->service->users->get(123);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(123, $user->id);
        $this->assertEquals('Bob', $user->first_name);
        $this->assertEquals('Smith', $user->last_name);
        $this->assertEquals('bob@example.com', $user->email);
    }

    public function test_can_list_users(): void
    {
        // Arrange - Create paginated response
        $users = [
            $this->mockUserData(['id' => 1, 'first_name' => 'Alice']),
            $this->mockUserData(['id' => 2, 'first_name' => 'Bob']),
        ];

        $this->mockGlobalRequests([
            Users::class => MockResponse::make([
                'items' => $users,
                'meta' => [
                    'pagination' => [
                        'current_page' => 1,
                        'next_page' => null,
                        'prev_page' => 0,
                        'total_pages' => 1,
                        'total_items' => 2,
                        'entries_info' => '1-2 of 2'
                    ]
                ]
            ], 200)
        ]);

        // Act
        $paginator = $this->service->users->users();
        $result = iterator_to_array($paginator->items());

        // Assert
        $this->assertCount(2, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals('Alice', $result[0]->first_name);
        $this->assertEquals('Bob', $result[1]->first_name);
    }

    public function test_can_find_user_by_email(): void
    {
        // Arrange
        $users = [
            $this->mockUserData(['email' => 'alice@example.com', 'first_name' => 'Alice'])
        ];

        $this->mockGlobalRequests([
            Users::class => MockResponse::make([
                'items' => $users,
                'meta' => [
                    'pagination' => [
                        'current_page' => 1,
                        'next_page' => null,
                        'prev_page' => 0,
                        'total_pages' => 1,
                        'total_items' => 1,
                        'entries_info' => '1-1 of 1'
                    ]
                ]
            ], 200)
        ]);

        // Act
        $user = $this->service->users->findByEmail('alice@example.com');

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('alice@example.com', $user->email);
        $this->assertEquals('Alice', $user->first_name);
    }

    public function test_find_by_email_returns_null_when_not_found(): void
    {
        // Arrange - Empty response
        $this->mockGlobalRequests([
            Users::class => MockResponse::make([
                'items' => [],
                'meta' => [
                    'pagination' => [
                        'current_page' => 1,
                        'next_page' => null,
                        'prev_page' => 0,
                        'total_pages' => 0,
                        'total_items' => 0,
                        'entries_info' => '0-0 of 0'
                    ]
                ]
            ], 200)
        ]);

        // Act
        $user = $this->service->users->findByEmail('nonexistent@example.com');

        // Assert
        $this->assertNull($user);
    }

    public function test_user_has_all_thinkific_fields(): void
    {
        // Arrange
        $userData = $this->mockUserData([
            'id' => 456,
            'affiliate_code' => 'ABC123',
            'affiliate_commission' => 25,
            'company' => 'Acme Corp',
            'custom_profile_fields' => [
                ['id' => 1, 'value' => '555-1234', 'label' => 'Phone']
            ]
        ]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($userData, 200)
        ]);

        // Act
        $user = $this->service->users->get(456);

        // Assert - Check Thinkific-specific fields
        $this->assertEquals('ABC123', $user->affiliate_code);
        $this->assertEquals(25, $user->affiliate_commission);
        $this->assertEquals('Acme Corp', $user->company);
        $this->assertIsArray($user->custom_profile_fields);
        $this->assertCount(1, $user->custom_profile_fields);
    }

    public function test_user_service_exists(): void
    {
        $this->assertInstanceOf(
            \WooNinja\ThinkificSaloon\Services\UserService::class,
            $this->service->users
        );
    }

    public function test_user_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service->users, 'get'));
        $this->assertTrue(method_exists($this->service->users, 'users'));
        $this->assertTrue(method_exists($this->service->users, 'create'));
        $this->assertTrue(method_exists($this->service->users, 'update'));
        $this->assertTrue(method_exists($this->service->users, 'delete'));
        $this->assertTrue(method_exists($this->service->users, 'findByEmail'));
    }
}
