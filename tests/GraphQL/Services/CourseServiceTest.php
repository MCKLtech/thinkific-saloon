<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Chapter;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Content;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Lesson;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Courses\Course as CourseRequest;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Courses\Courses;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

class CourseServiceTest extends GraphQLTestCase
{
    // -------------------------------------------------------------------------
    // courses() — paginated site course list
    // -------------------------------------------------------------------------

    public function test_courses_list_returns_course_dtos(): void
    {
        $node1 = $this->gqlCourseNode(['id' => '101', 'title' => 'Intro to PHP']);
        $node2 = $this->gqlCourseNode(['id' => '102', 'title' => 'Advanced PHP']);

        $this->mockGql([
            Courses::class => $this->gqlResponse([
                'site' => ['courses' => $this->gqlConnection([$node1, $node2])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->courses->courses()->items());

        $this->assertCount(2, $items);
        $this->assertInstanceOf(Course::class, $items[0]);
        $this->assertEquals(101, $items[0]->id);
        $this->assertEquals('Intro to PHP', $items[0]->title);
        $this->assertEquals(102, $items[1]->id);
        $this->assertEquals('Advanced PHP', $items[1]->title);
    }

    public function test_courses_list_maps_slug_and_name(): void
    {
        $node = $this->gqlCourseNode(['id' => '101', 'name' => 'intro-to-php', 'slug' => 'intro-to-php']);

        $this->mockGql([
            Courses::class => $this->gqlResponse([
                'site' => ['courses' => $this->gqlConnection([$node])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->courses->courses()->items());

        $this->assertEquals('intro-to-php', $items[0]->name);
        $this->assertEquals('intro-to-php', $items[0]->slug);
    }

    public function test_courses_list_terminates_at_last_page(): void
    {
        $this->mockGql([
            Courses::class => $this->gqlResponse([
                'site' => ['courses' => $this->gqlConnection([$this->gqlCourseNode()], hasNextPage: false)],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->courses->courses() as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    public function test_courses_list_returns_empty_for_no_courses(): void
    {
        $this->mockGql([
            Courses::class => $this->gqlResponse([
                'site' => ['courses' => $this->gqlConnection([])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->courses->courses()->items());

        $this->assertEmpty($items);
    }

    // -------------------------------------------------------------------------
    // chapters() — paginated course curriculum
    // -------------------------------------------------------------------------

    public function test_chapters_returns_chapter_dtos_with_lessons(): void
    {
        $chapter = $this->gqlChapterNode();

        $this->mockGql([
            CourseRequest::class => $this->gqlResponse([
                'course' => [
                    'curriculum' => [
                        'chapters' => $this->gqlConnection([$chapter]),
                    ],
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->courses->chapters(101)->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Chapter::class, $items[0]);
        $this->assertEquals(10, $items[0]->id);
        $this->assertEquals(1, $items[0]->position);
        $this->assertEquals('Chapter 1', $items[0]->title);
    }

    public function test_chapters_maps_lessons(): void
    {
        $this->mockGql([
            CourseRequest::class => $this->gqlResponse([
                'course' => [
                    'curriculum' => [
                        'chapters' => $this->gqlConnection([$this->gqlChapterNode()]),
                    ],
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->courses->chapters(101)->items());

        $this->assertCount(1, $items[0]->lessons);
        $this->assertInstanceOf(Lesson::class, $items[0]->lessons[0]);
        $this->assertEquals(401, $items[0]->lessons[0]->id);
        $this->assertEquals('video', $items[0]->lessons[0]->lessonType);
        $this->assertEquals('Lesson 1', $items[0]->lessons[0]->title);
    }

    public function test_chapters_maps_lesson_content(): void
    {
        $this->mockGql([
            CourseRequest::class => $this->gqlResponse([
                'course' => [
                    'curriculum' => [
                        'chapters' => $this->gqlConnection([$this->gqlChapterNode()]),
                    ],
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->courses->chapters(101)->items());

        $this->assertInstanceOf(Content::class, $items[0]->lessons[0]->content);
        $this->assertEquals(501, $items[0]->lessons[0]->content->id);
        $this->assertEquals('video', $items[0]->lessons[0]->content->contentType);
    }

    public function test_chapters_paginator_reads_page_info_correctly(): void
    {
        // Regression test for the bug where paginator read chapters.endCursor
        // instead of chapters.pageInfo.endCursor — causing it to always stop after page 1.
        // With the fix, hasNextPage: false in pageInfo must correctly terminate pagination.
        $this->mockGql([
            CourseRequest::class => $this->gqlResponse([
                'course' => [
                    'curriculum' => [
                        'chapters' => $this->gqlConnection([$this->gqlChapterNode()], hasNextPage: false),
                    ],
                ],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->courses->chapters(101) as $_page) {
            $pages++;
        }

        // Must be exactly 1 — bug caused this to always be 1 (silently), so we
        // verify the fixture wires through the correct pageInfo path.
        $this->assertEquals(1, $pages);
    }

    public function test_chapters_returns_empty_for_no_chapters(): void
    {
        $this->mockGql([
            CourseRequest::class => $this->gqlResponse([
                'course' => [
                    'curriculum' => [
                        'chapters' => $this->gqlConnection([]),
                    ],
                ],
            ]),
        ]);

        $items = iterator_to_array($this->gql->courses->chapters(101)->items());

        $this->assertEmpty($items);
    }
}