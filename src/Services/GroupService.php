<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Groups\Group;
use WooNinja\ThinkificSaloon\Requests\Groups\AddUser;
use WooNinja\ThinkificSaloon\Requests\Groups\Analysts;
use WooNinja\ThinkificSaloon\Requests\Groups\AssignAnalysts;
use WooNinja\ThinkificSaloon\Requests\Groups\Create;
use WooNinja\ThinkificSaloon\Requests\Groups\Delete;
use WooNinja\ThinkificSaloon\Requests\Groups\Get;
use WooNinja\ThinkificSaloon\Requests\Groups\Groups;
use WooNinja\ThinkificSaloon\Requests\Groups\RemoveAnalyst;
use WooNinja\ThinkificSaloon\Requests\Users\Users;

class GroupService extends Resource
{

    /**
     * Get a Group by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Groups/getGroup
     *
     * @param int $group_id
     * @return Group
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $group_id): Group
    {
        return $this->connector
            ->send(new Get($group_id))
            ->dtoOrFail();
    }

    /**
     * List all groups
     * @see https://developers.thinkific.com/api/api-documentation/#/Groups/getGroup
     *
     * @return PagedPaginator
     */
    public function groups(): PagedPaginator
    {
        return $this->connector
            ->paginate(new Groups());
    }

    /**
     * Create a Group
     * @see https://developers.thinkific.com/api/api-documentation/#/Groups/createGroup
     *
     * @param string $name
     * @return Group
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(string $name): Group
    {
        return $this->connector
            ->send(new Create($name))
            ->dtoOrFail();
    }

    /**
     * Delete a Group
     * https://developers.thinkific.com/api/api-documentation/#/Groups/deleteGroupByID
     *
     * @param int $group_id
     * @return void
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function delete(int $group_id): void
    {
        $this->connector
            ->send(new Delete($group_id));
    }

    /**
     * Retrieve the users of a given group
     * @see https://developers.thinkific.com/api/api-documentation/#/Users/getUsers
     *
     * @param $group_id
     * @return PagedPaginator
     */
    public function users($group_id, array $params = []): PagedPaginator
    {
        $filter = ['query[group_id]' => $group_id];

        if(!empty($params)) {
            $filter = array_merge($filter, $params);
        }

        return $this->connector
            ->paginate(new Users($filter));
    }

    /**
     * Add a user to a given group(s)
     * @see https://developers.thinkific.com/api/api-documentation/#/Group%20Users/createGroupUsers
     *
     * @param int $user_id
     * @param array $group_names
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function addUser(int $user_id, array $group_names): Response
    {
        return $this->connector
            ->send(new AddUser($user_id, $group_names));
    }

    /**
     * Determine if a user is in a group
     *
     * @param int|string $user_id_or_email
     * @param int $group_id
     * @return bool
     */
    public function isUserInGroup(string $email, int $group_id): bool
    {
        $filter = [
            'limit' => 1,
            'query[email]' => $email
        ];

        $user = $this->users($group_id, $filter)->collect();

        if($user->isEmpty()) {
            return false;
        }

        return true;
    }

    /**
     * The Analysts for a given Group
     * @see https://developers.thinkific.com/api/api-documentation/#/Groups/getGroupAnalysts
     *
     * @param int $group_id
     * @return array
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function analysts(int $group_id): array
    {
        return $this->connector
            ->send(new Analysts($group_id))
            ->dtoOrFail();
    }

    /**
     * Assign Analysts to a Group
     * @see https://developers.thinkific.com/api/api-documentation/#/Groups/postGroupAnalysts
     *
     * @param int $group_id
     * @param array $user_ids
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function assignAnalysts(int $group_id, array $user_ids): Response
    {
        return $this->connector
            ->send(new AssignAnalysts($group_id, $user_ids));
    }

    /**
     * Remove an analyst from a Group
     * @see https://developers.thinkific.com/api/api-documentation/#/Groups/removeAnalystFromGroup
     *
     * @param int $group_id
     * @param int $user_id
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function removeAnalyst(int $group_id, int $user_id): Response
    {
        return $this->connector
            ->send(new RemoveAnalyst($group_id, $user_id));
    }

}