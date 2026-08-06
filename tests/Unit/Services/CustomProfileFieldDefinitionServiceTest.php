<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\CustomProfileFieldDefinitions\CustomProfileFieldDefinition;
use WooNinja\ThinkificSaloon\Requests\CustomProfileFieldDefinitions\CustomProfileFieldDefinition as CustomProfileFieldDefinitionRequest;
use WooNinja\ThinkificSaloon\Requests\Users\Users;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class CustomProfileFieldDefinitionServiceTest extends TestCase
{
    public function test_can_list_definitions(): void
    {
        $definitions = [
            $this->mockCustomProfileFieldDefinitionData(['id' => 1, 'label' => 'Phone']),
            $this->mockCustomProfileFieldDefinitionData(['id' => 2, 'label' => 'Company']),
        ];

        $this->mockGlobalRequests([
            CustomProfileFieldDefinitionRequest::class => MockResponse::make([
                'items' => $definitions,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->custom_profile_field_definitions->definitions()->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(CustomProfileFieldDefinition::class, $result[0]);
        $this->assertEquals('Phone', $result[0]->label);
        $this->assertEquals('Company', $result[1]->label);
    }

    public function test_search_users_by_custom_profile_field(): void
    {
        $users = [$this->mockUserData(['id' => 1])];

        $this->mockGlobalRequests([
            Users::class => MockResponse::make([
                'items' => $users,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 1, 'entries_info' => '1-1 of 1',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array(
            $this->service->custom_profile_field_definitions
                ->searchUsers('Phone', '887 909 9999')
                ->items()
        );

        $this->assertCount(1, $result);
    }
}
