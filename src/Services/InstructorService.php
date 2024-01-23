<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\CreateInstructor;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\Instructor;
use WooNinja\ThinkificSaloon\DataTransferObjects\Instructors\UpdateInstructor;
use WooNinja\ThinkificSaloon\Requests\Instructors\Create;
use WooNinja\ThinkificSaloon\Requests\Instructors\Delete;
use WooNinja\ThinkificSaloon\Requests\Instructors\Get;
use WooNinja\ThinkificSaloon\Requests\Instructors\Instructors;
use WooNinja\ThinkificSaloon\Requests\Instructors\Update;

class InstructorService extends Resource
{

    /**
     * Get an instructor from Thinkific by ID
     * @ https://developers.thinkific.com/api/api-documentation/#/Instructors/getInstructorByID
     *
     * @param int $instructorId
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function get(int $instructorId)
    {
        return $this->connector
            ->send(new Get($instructorId))
            ->dtoOrFail();
    }

    /**
     * Get all instructors from Thinkific
     * @see https://developers.thinkific.com/api/api-documentation/#/Instructors/getInstructors
     *
     * @return PagedPaginator
     */
    public function instructors()
    {
        return $this->connector
            ->paginate(new Instructors());
    }

    /**
     * Create a new instructor in Thinkific
     * @see https://developers.thinkific.com/api/api-documentation/#/Instructors/createInstructor
     *
     * @param CreateInstructor $instructor
     * @return Instructor
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateInstructor $instructor): Instructor
    {
        return $this->connector
            ->send(new Create($instructor))
            ->dtoOrFail();
    }

    /**
     * Update an instructor in Thinkific
     * @see https://developers.thinkific.com/api/api-documentation/#/Instructors/updateInstructor
     *
     * @param UpdateInstructor $instructor
     * @return Instructor
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function update(UpdateInstructor $instructor): Instructor
    {
        return $this->connector
            ->send(new Update($instructor))
            ->dtoOrFail();
    }

    /**
     * Delete an instructor
     * @see https://developers.thinkific.com/api/api-documentation/#/Instructors/deleteInstructorByID
     *
     * @param int $instructorId
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function delete(int $instructorId): Response
    {
        return $this->connector
            ->send(new Delete($instructorId));

    }

}