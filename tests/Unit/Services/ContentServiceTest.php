<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Contents\Content;
use WooNinja\ThinkificSaloon\Requests\Contents\Get;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class ContentServiceTest extends TestCase
{
    public function test_can_get_content_by_id(): void
    {
        $contentData = $this->mockContentData(['id' => 7, 'name' => 'Intro Video', 'free' => true]);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($contentData, 200),
        ]);

        $content = $this->service->contents->get(7);

        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals(7, $content->id);
        $this->assertEquals('Intro Video', $content->name);
        $this->assertTrue($content->free);
        $this->assertEquals('Lesson', $content->contentable_type);
    }
}
