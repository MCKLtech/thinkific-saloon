<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\CreateInstructor;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\Instructor;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\UpdateInstructor;
use WooNinja\ThinkificSaloon\Requests\Instructors\Create;
use WooNinja\ThinkificSaloon\Requests\Instructors\Delete;
use WooNinja\ThinkificSaloon\Requests\Instructors\Get;
use WooNinja\ThinkificSaloon\Requests\Instructors\Instructors;
use WooNinja\ThinkificSaloon\Requests\Instructors\Update;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class InstructorServiceTest extends TestCase
{
    public function test_can_get_instructor_by_id(): void
    {
        $instructorData = $this->mockInstructorData(['id' => 3, 'first_name' => 'Ada']);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($instructorData, 200),
        ]);

        $instructor = $this->service->instructors->get(3);

        $this->assertInstanceOf(Instructor::class, $instructor);
        $this->assertEquals(3, $instructor->id);
        $this->assertEquals('Ada', $instructor->first_name);
    }

    public function test_can_list_instructors(): void
    {
        $instructors = [
            $this->mockInstructorData(['id' => 1]),
            $this->mockInstructorData(['id' => 2]),
        ];

        $this->mockGlobalRequests([
            Instructors::class => MockResponse::make([
                'items' => $instructors,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->instructors->instructors()->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Instructor::class, $result[0]);
    }

    public function test_can_create_instructor(): void
    {
        $instructorData = $this->mockInstructorData(['id' => 9, 'first_name' => 'New']);

        $this->mockGlobalRequests([
            Create::class => MockResponse::make($instructorData, 201),
        ]);

        $instructor = $this->service->instructors->create(new CreateInstructor(
            first_name: 'New',
            last_name: 'Instructor',
            email: 'new@example.com',
            title: null,
            user_id: null,
            bio: null,
            slug: 'new-instructor',
            avatar_url: null,
        ));

        $this->assertEquals(9, $instructor->id);
        $this->assertEquals('New', $instructor->first_name);
    }

    public function test_can_update_instructor(): void
    {
        $instructorData = $this->mockInstructorData(['id' => 3, 'first_name' => 'Updated']);

        $this->mockGlobalRequests([
            Update::class => MockResponse::make($instructorData, 200),
        ]);

        $instructor = $this->service->instructors->update(new UpdateInstructor(
            id: 3,
            first_name: 'Updated',
            last_name: 'Doe',
            email: null,
            title: null,
            user_id: null,
            bio: null,
            slug: 'updated-doe',
            avatar_url: null,
        ));

        $this->assertEquals('Updated', $instructor->first_name);
    }

    public function test_can_delete_instructor(): void
    {
        $this->mockGlobalRequests([
            Delete::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->instructors->delete(3);

        $this->assertResponseSuccessful($response);
    }

    /**
     * Regression guard: Thinkific's InstructorRequest schema requires slug
     * on both create and update, even though every other field is optional.
     * A nullable slug lets a caller construct a request the real API will
     * reject with a 422, so it must stay a required (non-nullable, no
     * default) constructor argument on both DTOs.
     */
    public function test_slug_is_required_on_create_and_update_dtos(): void
    {
        foreach ([CreateInstructor::class, UpdateInstructor::class] as $class) {
            $params = (new \ReflectionClass($class))->getConstructor()->getParameters();
            $slug = current(array_filter($params, fn(\ReflectionParameter $p) => $p->getName() === 'slug'));

            $this->assertNotFalse($slug, "{$class} should have a \$slug constructor parameter");
            $this->assertFalse($slug->allowsNull(), "{$class}::\$slug should not be nullable");
            $this->assertFalse($slug->isDefaultValueAvailable(), "{$class}::\$slug should not have a default value");
        }
    }
}
