<?php

namespace WooNinja\ThinkificSaloon\Services;


use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use WooNinja\ThinkificSaloon\DataTransferObjects\Contents\Content;
use WooNinja\ThinkificSaloon\Requests\Contents\Get;



class ContentService extends Resource
{
    /**
     * Get a Content of a Chapter by its ID.
     * @see https://developers.thinkific.com/api/api-documentation/#/Contents/getContentByID
     * @param int $content_id
     * @return Content
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $content_id): Content
    {
        return $this->connector
            ->send(new Get($content_id))
            ->dtoOrFail();
    }



}