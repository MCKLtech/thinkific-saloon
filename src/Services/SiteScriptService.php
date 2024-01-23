<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\CreateSiteScript;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\SiteScript;
use WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts\UpdateSiteScript;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Create;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Delete;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Get;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Scripts;
use WooNinja\ThinkificSaloon\Requests\SiteScripts\Update;

class SiteScriptService extends Resource
{

    /**
     * Get a Site Script by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Site%20Scripts/getSiteScriptByID
     *
     * @param int $script_id
     * @return SiteScript
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $script_id): SiteScript
    {
        return $this->connector
            ->send(new Get($script_id))
            ->dtoOrFail();
    }

    /**
     * Create a Site Script
     * @see https://developers.thinkific.com/api/api-documentation/#/Site%20Scripts/createSiteScript
     *
     * @param CreateSiteScript $script
     * @return SiteScript
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateSiteScript $script): SiteScript
    {
        return $this->connector
            ->send(new Create($script))
            ->dtoOrFail();
    }

    /**
     * Update Site Script by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Site%20Scripts/updateSiteScriptByID
     *
     * @param UpdateSiteScript $script
     * @return SiteScript
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function update(UpdateSiteScript $script): SiteScript
    {
        return $this->connector
            ->send(new Update($script))
            ->dtoOrFail();
    }

    /**
     * Delete a site script
     * @see https://developers.thinkific.com/api/api-documentation/#/Site%20Scripts/deleteSiteScriptByID
     *
     * @param int $script_id
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function delete(int $script_id): Response
    {
        return $this->connector
            ->send(new Delete($script_id));
    }

    /**
     * Get Site Scripts
     * @see https://developers.thinkific.com/api/api-documentation/#/Site%20Scripts/getSiteScripts
     *
     * @return PagedPaginator
     */
    public function scripts(): PagedPaginator
    {
        return $this->connector
            ->paginate(new Scripts());
    }

}