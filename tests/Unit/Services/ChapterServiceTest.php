<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Chapters\Chapter;
use WooNinja\ThinkificSaloon\Requests\Chapters\Contents;
use WooNinja\ThinkificSaloon\Requests\Chapters\Get;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class ChapterServiceTest extends TestCase
{
    public function test_can_get_chapter_by_id(): void
    {
        $chapterData = $this->mockChapterData(['id' => 5, 'name' => 'Chapter One']);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($chapterData, 200),
        ]);

        $chapter = $this->service->chapters->get(5);

        $this->assertInstanceOf(Chapter::class, $chapter);
        $this->assertEquals(5, $chapter->id);
        $this->assertEquals('Chapter One', $chapter->name);
        $this->assertEquals([1, 2], $chapter->content_ids);
    }

    public function test_can_list_chapter_content(): void
    {
        $content = [
            $this->mockContentData(['id' => 1, 'name' => 'Lesson 1']),
            $this->mockContentData(['id' => 2, 'name' => 'Lesson 2']),
        ];

        $this->mockGlobalRequests([
            Contents::class => MockResponse::make([
                'items' => $content,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $paginator = $this->service->chapters->content(5);
        $result = iterator_to_array($paginator->items());

        $this->assertCount(2, $result);
        $this->assertEquals('Lesson 1', $result[0]->name);
        $this->assertEquals('Lesson 2', $result[1]->name);
    }
}
