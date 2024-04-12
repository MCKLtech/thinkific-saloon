<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\CreateUser;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\UpdateUser;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\Requests\Users\Create;
use WooNinja\ThinkificSaloon\Requests\Users\Delete;
use WooNinja\ThinkificSaloon\Requests\Users\Get;
use WooNinja\ThinkificSaloon\Requests\Users\Update;
use WooNinja\ThinkificSaloon\Requests\Users\Users;

class UserService extends Resource
{

    /**
     * Get a User by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Users/getUserByID
     *
     * @param int $user_id
     * @return User
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $user_id): User
    {
        return $this->connector
            ->send(new Get($user_id))
            ->dtoOrFail();
    }

    /**
     * Get a list of Users
     * @see https://developers.thinkific.com/api/api-documentation/#/Users/getUsers
     *
     * @param array $filters
     * @return PagedPaginator
     */
    public function users(array $filters = []): PagedPaginator
    {
        return $this->connector
            ->paginate(new Users($filters));
    }

    /**
     * Create a User
     * @see https://developers.thinkific.com/api/api-documentation/#/Users/createUser
     *
     * @param CreateUser $user
     * @return User
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateUser $user): User
    {
        return $this->connector
            ->send(new Create($user))
            ->dtoOrFail();
    }

    /**
     * Update a User
     *
     * @param UpdateUser $user
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function update(UpdateUser $user): Response
    {
        return $this->connector
            ->send(new Update($user));
    }

    /**
     * Delete a User
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function delete(int $user_id): void
    {
        $this->connector
            ->send(new Delete($user_id));
    }

    /**
     * Search for a user by ID or email
     *
     * @param string|int $user_id_or_email
     * @return User|null
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function find(string|int $user_id_or_email): User|null
    {
        if (is_numeric($user_id_or_email)) {
            return $this->get($user_id_or_email);
        }

        return $this->findByEmail($user_id_or_email);
    }

    /**
     * Get a user by exact email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): User|null
    {
        $users = $this->users(
            [
                'query[email]' => $email,
                'limit' => 1
            ]
        )->collect();

        if ($users->count() > 0) {
            return $users->first();
        }

        return null;
    }
}