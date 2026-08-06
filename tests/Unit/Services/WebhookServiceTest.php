<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Services;

use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\CreateWebhook;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\UpdateWebhook;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\Webhook;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Create;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Delete;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Get;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Update;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Webhooks;
use WooNinja\ThinkificSaloon\Tests\TestCase;

/**
 * Webhooks require an OAuth connection via the v2 API, so WebhookService
 * forces $this->service->is_oauth = true and swaps the connector's base_url
 * to api/v2/ before every request (see WebhookService::getConnector()).
 * Fixture data mirrors the real response shape documented in CLAUDE.md.
 */
class WebhookServiceTest extends TestCase
{
    public function test_can_get_webhook_by_id(): void
    {
        $webhookData = $this->mockWebhookData(['id' => '01KC4KC2FW5JQM12345', 'topic' => 'lesson.completed']);

        $this->mockGlobalRequests([
            Get::class => MockResponse::make($webhookData, 200),
        ]);

        $webhook = $this->service->webhooks->get('01KC4KC2FW5JQM12345');

        $this->assertInstanceOf(Webhook::class, $webhook);
        $this->assertEquals('01KC4KC2FW5JQM12345', $webhook->id);
        $this->assertEquals('lesson.completed', $webhook->topic);
    }

    public function test_can_list_webhooks(): void
    {
        $webhooks = [
            $this->mockWebhookData(['id' => '1', 'topic' => 'lesson.completed']),
            $this->mockWebhookData(['id' => '2', 'topic' => 'order.created']),
        ];

        $this->mockGlobalRequests([
            Webhooks::class => MockResponse::make([
                'items' => $webhooks,
                'meta' => ['page' => ['has_next' => false, 'next_page' => '', 'page_items' => 2, 'total_items' => 2]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->webhooks->webhooks()->items());

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Webhook::class, $result[0]);
    }

    public function test_list_webhooks_follows_the_v2_has_next_flag_across_multiple_pages(): void
    {
        // Regression test: the v2 API (Webhooks only) reports pagination
        // under meta.page.has_next, not v1's meta.pagination.next_page.
        // Before ThinkificConnector::paginate() understood that shape, the
        // paginator would silently stop after page 1 for every v2 endpoint
        // no matter how many pages actually existed.
        // Sequential (unkeyed) mocks: the first request gets the first
        // response, the second request gets the second, regardless of
        // request class.
        $this->mockGlobalRequests([
            MockResponse::make([
                'items' => [$this->mockWebhookData(['id' => '1'])],
                'meta' => ['page' => ['has_next' => true, 'next_page' => '2', 'page_items' => 1, 'total_items' => 2]],
            ], 200),
            MockResponse::make([
                'items' => [$this->mockWebhookData(['id' => '2'])],
                'meta' => ['page' => ['has_next' => false, 'next_page' => '', 'page_items' => 1, 'total_items' => 2]],
            ], 200),
        ]);

        $result = iterator_to_array($this->service->webhooks->webhooks()->items());

        $this->assertCount(2, $result);
    }

    public function test_can_create_webhook(): void
    {
        $webhookData = $this->mockWebhookData(['id' => '99', 'topic' => 'order.created']);

        $this->mockGlobalRequests([
            Create::class => MockResponse::make($webhookData, 201),
        ]);

        $webhook = $this->service->webhooks->create(new CreateWebhook(
            topic: 'order.created',
            target_url: 'https://example.net/webhook-ingest',
        ));

        $this->assertEquals('99', $webhook->id);
        $this->assertEquals('order.created', $webhook->topic);
    }

    public function test_can_update_webhook(): void
    {
        $webhookData = $this->mockWebhookData(['id' => '1', 'topic' => 'order.updated']);

        $this->mockGlobalRequests([
            Update::class => MockResponse::make($webhookData, 200),
        ]);

        $webhook = $this->service->webhooks->update(new UpdateWebhook(
            id: '1',
            topic: 'order.updated',
            target_url: 'https://example.net/webhook-ingest',
        ));

        $this->assertEquals('order.updated', $webhook->topic);
    }

    public function test_can_delete_webhook(): void
    {
        $this->mockGlobalRequests([
            Delete::class => MockResponse::make([], 200),
        ]);

        $response = $this->service->webhooks->delete('1');

        $this->assertTrue($response->successful());
    }

    public function test_has_returns_true_when_a_matching_webhook_exists(): void
    {
        $this->mockGlobalRequests([
            Webhooks::class => MockResponse::make([
                'items' => [$this->mockWebhookData(['topic' => 'lesson.completed', 'target_url' => 'https://example.net/hook'])],
                'meta' => ['page' => ['has_next' => false, 'next_page' => '', 'page_items' => 1, 'total_items' => 1]],
            ], 200),
        ]);

        $this->assertTrue($this->service->webhooks->has('lesson.completed', 'https://example.net/hook'));
    }

    public function test_has_returns_false_when_no_webhook_matches(): void
    {
        $this->mockGlobalRequests([
            Webhooks::class => MockResponse::make([
                'items' => [$this->mockWebhookData(['topic' => 'lesson.completed', 'target_url' => 'https://example.net/hook'])],
                'meta' => ['page' => ['has_next' => false, 'next_page' => '', 'page_items' => 1, 'total_items' => 1]],
            ], 200),
        ]);

        $this->assertFalse($this->service->webhooks->has('order.created', 'https://example.net/other'));
    }
}
