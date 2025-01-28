<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Groups\Groups;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Groups\Users;

class GroupService extends Resource
{
    /**
     * Return the Users that are part of a given Group
     *
     * @param int $group_id
     * @param int $per_page
     * @return Paginator
     */
    public function users(int $group_id, int $per_page = 5): Paginator
    {
        $users = new Users($group_id, $per_page);

        return $users->paginate($this->connector);
    }

    /**
     * Return Thinkific Groups in the Site
     *
     * @param int $per_page
     * @return Paginator
     */
    public function groups(int $per_page = 100): Paginator
    {
        $groups = new Groups($per_page);

        return $groups->paginate($this->connector);
    }
}