<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\CreateSiteScript;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\SiteScript;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\UpdateSiteScript;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Create;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Delete;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Get;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Scripts;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Update;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class SiteScriptServiceTest extends TestCase
{
    public function test_can_get_site_script_by_id(): void
    {
        $scriptData = $this->mockSiteScriptData(['id' => '5', 'name' => 'Analytics']);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($scriptData, 200),
        ]);

        $script = $this->service->site_scripts->get(5);

        $this->assertInstanceOf(SiteScript::class, $script);
        $this->assertEquals('5', $script->id);
        $this->assertEquals('Analytics', $script->name);
    }

    public function test_can_list_site_scripts(): void
    {
        $scripts = [
            $this->mockSiteScriptData(['id' => '1']),
            $this->mockSiteScriptData(['id' => '2']),
        ];

        $this->mockGlobalRequests([
            Scripts::class => MockResponse::make([
                'items' => $scripts,
                'meta' => ['pagination' => [
                    'current_page' => 1, 'next_page' => null, 'prev_page' => 0,
                    'total_pages' => 1, 'total_items' => 2, 'entries_info' => '1-2 of 2',
                ]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->site_scripts->scripts()->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(SiteScript::class, $result[0]);
    }

    public function test_can_create_site_script(): void
    {
        // Create's response is nested under a "site_script" key, unlike Get/Update.
        $this->mockGlobalRequests([
            Create::class => MockResponse::make([
                'site_script' => $this->mockSiteScriptData(['id' => '9', 'name' => 'New Script']),
            ], 201),
        ]);

        $script = $this->service->site_scripts->create(new CreateSiteScript(
            name: 'New Script',
            description: 'Tracking script',
            page_scopes: ['all'],
        ));

        $this->assertEquals('9', $script->id);
        $this->assertEquals('New Script', $script->name);
    }

    public function test_can_update_site_script(): void
    {
        $scriptData = $this->mockSiteScriptData(['id' => '5', 'name' => 'Updated Script']);

        $this->mockGlobalRequests([
            Update::class => MockResponse::make($scriptData, 200),
        ]);

        $script = $this->service->site_scripts->update(new UpdateSiteScript(
            id: 5,
            name: 'Updated Script',
            description: 'Tracking script',
            page_scopes: ['all'],
            src: null,
            content: "console.log('hi')",
            location: 'footer',
            load_method: 'default',
            category: 'functional',
        ));

        $this->assertEquals('Updated Script', $script->name);
    }

    public function test_can_delete_site_script(): void
    {
        $this->mockGlobalRequests([
            Delete::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->site_scripts->delete('5');

        $this->assertTrue($response->successful());
    }
}
