<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;


use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Assignments;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Get;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\GetByEmail;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Groups;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Users\Users;


class UserService extends Resource
{

    /**
     * Get a User by their GID
     * @param string $gid
     * @return User
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(string $gid): User
    {
        return $this->connector
            ->send(new Get($gid))
            ->dtoOrFail();
    }

    /**
     * Return a list of Users
     *
     * @param int $per_page
     * @return Paginator
     */
    public function users(int $per_page = 100): Paginator
    {
        $users = new Users($per_page);

        return $users->paginate($this->connector);

    }

    /**
     * Get a user by email address
     *
     * @param string $email
     * @return User
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getByEmail(string $email): User
    {
        return $this->connector
            ->send(new GetByEmail($email))
            ->dtoOrFail();
    }

    /**
     * Get groups associated with a user by GID or email
     *
     * @param string $gid_or_email
     * @return array
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function groups(string $gid_or_email): array
    {
        return $this->connector
            ->send(new Groups($gid_or_email))
            ->dtoOrFail();
    }

    public function assignments(string $gid, int $per_page = 100): Paginator
    {
        $assignments = new Assignments($gid, $per_page);

        return $assignments->paginate($this->connector);
    }

}