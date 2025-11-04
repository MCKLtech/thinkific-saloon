<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\LMSContracts\Contracts\Services\UserServiceInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Users\UserInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Users\CreateUserInterface;
use WooNinja\LMSContracts\Contracts\DTOs\Users\UpdateUserInterface;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\CreateUser;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\UpdateUser;
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\Requests\Users\Create;
use WooNinja\ThinkificSaloon\Requests\Users\Delete;
use WooNinja\ThinkificSaloon\Requests\Users\Get;
use WooNinja\ThinkificSaloon\Requests\Users\Update;
use WooNinja\ThinkificSaloon\Requests\Users\Users;

class UserService extends Resource implements UserServiceInterface
{

    /**
     * Get a User by ID
     * @see https://developers.thinkific.com/api/api-documentation/#/Users/getUserByID
     *
     * @param int $user_id
     * @return UserInterface
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $user_id): UserInterface
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
     * @param CreateUserInterface $user
     * @return UserInterface
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateUserInterface $user): UserInterface
    {
        return $this->connector
            ->send(new Create($user))
            ->dtoOrFail();
    }

    /**
     * Update a User
     *
     * @param UpdateUserInterface $user
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function update(UpdateUserInterface $user): Response
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
     * @return UserInterface|null
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function find(string|int $user_id_or_email): UserInterface|null
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
     * @return UserInterface|null
     */
    public function findByEmail(string $email): ?UserInterface
    {
        $response = $this->connector->send(new Users([
            'query[email]' => $email,
            'limit' => 1,
        ]));

        $users = $response->dto();

        if (!empty($users) && count($users) > 0) {

            return $users[0] ?? null;
        }

        return null;

    }
}