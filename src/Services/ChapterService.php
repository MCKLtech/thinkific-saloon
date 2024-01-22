<?php

namespace WooNinja\ThinkificSaloon\Services;


use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Chapters\Chapter;
use WooNinja\ThinkificSaloon\Requests\Chapters\Contents;
use WooNinja\ThinkificSaloon\Requests\Chapters\Get;

class ChapterService extends Resource
{
    /**
     * Get a Course Chapter by its ID.
     * @see https://developers.thinkific.com/api/api-documentation/#/Chapters/getChapterByID
     * @param int $chapter_id
     * @return Chapter
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $chapter_id): Chapter
    {
        return $this->connector
            ->send(new Get($chapter_id))
            ->dtoOrFail();
    }

    /**
     * Get a list of chapter contents
     * @see https://developers.thinkific.com/api/api-documentation/#/Chapters/getContentsByID
     * @param int $chapter_id
     * @return PagedPaginator
     */
    public function content(int $chapter_id): PagedPaginator
    {
        return $this->connector
            ->paginate(new Contents($chapter_id));
    }


}