<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Traits;

use ArgumentCountError;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\ReadEnrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Enrollments\UpdateEnrollment;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\UpdateUser;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class MapperTraitTest extends TestCase
{
    public function test_maps_matching_properties_from_source_to_target(): void
    {
        // The documented usage from CLAUDE.md: $client->mapDTO($user, UpdateUser::class)
        $user = new User(
            id: 1,
            first_name: 'Bob',
            last_name: 'Smith',
            email: 'bob@example.com',
            password: null,
            roles: ['student'],
            avatar_url: 'https://example.com/avatar.jpg',
            bio: 'A bio',
            company: 'Acme',
            headline: 'A headline',
            external_source: null,
            affiliate_code: null,
            affiliate_commission: null,
            affiliate_commission_type: null,
            affiliate_payout_email: null,
            custom_profile_fields: null,
        );

        $updateUser = $this->service->mapDTO($user, UpdateUser::class);

        $this->assertInstanceOf(UpdateUser::class, $updateUser);
        $this->assertEquals(1, $updateUser->id);
        $this->assertEquals('Bob', $updateUser->first_name);
        $this->assertEquals('Smith', $updateUser->last_name);
        $this->assertEquals('bob@example.com', $updateUser->email);
        $this->assertEquals(['student'], $updateUser->roles);
        $this->assertEquals('Acme', $updateUser->company);
    }

    public function test_ignores_source_properties_not_present_on_target(): void
    {
        // User::$created_at has no counterpart on UpdateUser - mapDTO only
        // iterates the target's properties, so it should simply be dropped.
        $user = new User(
            id: 1,
            first_name: 'Bob',
            last_name: 'Smith',
            email: 'bob@example.com',
            password: null,
            roles: [],
            avatar_url: null,
            bio: null,
            company: null,
            headline: null,
            external_source: null,
            affiliate_code: null,
            affiliate_commission: null,
            affiliate_commission_type: null,
            affiliate_payout_email: null,
            custom_profile_fields: null,
            created_at: \Carbon\Carbon::now(),
        );

        $updateUser = $this->service->mapDTO($user, UpdateUser::class);

        $this->assertFalse(property_exists($updateUser, 'created_at'));
    }

    public function test_throws_when_target_requires_a_property_the_source_does_not_have(): void
    {
        // UpdateEnrollment requires activated_at/expiry_date with no default;
        // ReadEnrollment doesn't have them, so the resulting constructor call
        // is missing required arguments.
        $readEnrollment = new ReadEnrollment(enrollment_id: 1, user_id: 1, course_id: 1);

        $this->expectException(ArgumentCountError::class);

        $this->service->mapDTO($readEnrollment, UpdateEnrollment::class);
    }
}
