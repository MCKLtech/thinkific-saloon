<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\Connectors\ThinkificConnector;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\CreateWebhook;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\UpdateWebhook;
use WooNinja\ThinkificSaloon\DataTransferObjects\Webhooks\Webhook;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Create;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Delete;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Get;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Update;
use WooNinja\ThinkificSaloon\Requests\Webhooks\Webhooks;

class WebhookService extends Resource
{

    /**
     * Webhooks require an OAuth connection via the V2 API endpoint
     * @see https://developers.thinkific.com/api/webhooks-api
     *
     * @return ThinkificConnector
     */
    private function getConnector(): ThinkificConnector
    {
        $this->service->is_oauth = true;

        /**
         * We need to reset the connector to force the authenticator to use a Bearer token
         */
        $this->service->resetService();

        $connector = $this->service->connector();

        $connector->base_url = "https://api.thinkific.com/api/v2/";

        return $connector;
    }

    /**
     * Get a webhook by ID
     * @see https://developers.thinkific.com/api/webhooks-api#/Webhooks/getWebhookByID
     *
     * @param string $webhook_id
     * @return Webhook
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(string $webhook_id): Webhook
    {
        return $this->getConnector()
            ->send(new Get($webhook_id))
            ->dtoOrFail();
    }

    /**
     * Create a Webhook
     * @see https://developers.thinkific.com/api/webhooks-api#/Webhooks/post_webhooks
     *
     * @param CreateWebhook $webhook
     * @return Webhook
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateWebhook $webhook): Webhook
    {
        return $this->getConnector()
            ->send(new Create($webhook))
            ->dtoOrFail();
    }

    /**
     * Update a Webhook
     * @see https://developers.thinkific.com/api/webhooks-api#/Webhooks/updateWebhookByID
     *
     * @param UpdateWebhook $webhook
     * @return Webhook
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function update(UpdateWebhook $webhook): Webhook
    {
        return $this->getConnector()
            ->send(new Update($webhook))
            ->dtoOrFail();
    }

    /**
     * Delete a webhook
     * @see https://developers.thinkific.com/api/webhooks-api#/Webhooks/deleteWebhookByID
     * @param string $webhook_id
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function delete(string $webhook_id): Response
    {
        return $this->getConnector()
            ->send(new Delete($webhook_id));
    }

    /**
     * List webhooks
     * @see https://developers.thinkific.com/api/webhooks-api#/Webhooks/get_webhooks
     *
     * @return PagedPaginator
     */
    public function webhooks(): PagedPaginator
    {
        return $this->getConnector()
            ->paginate(new Webhooks());
    }

    /**
     * Determine if a webhooks exists for a given topic and URL
     *
     * @param string $topic
     * @param string $url
     * @return bool
     */
    public function has(string $topic, string $url): bool
    {
        foreach($this->webhooks()->items() as $item) {
            if($item->topic === $topic && $item->target_url === $url) return true;
        }

        return false;
    }
}