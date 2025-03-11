<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;


use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments\UpdateAssignment;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Assignments\Assignments;


class AssignmentService extends Resource
{
    /**
     * Return a list of all assignments
     *
     * @param int $per_page
     * @return Paginator
     */
    public function assignments(int $per_page = 50): Paginator
    {
        $assignments = new Assignments($per_page);

        return $assignments->paginate($this->connector);

    }

    /**
     * Return list of user assignments
     *
     * @param int $user_id
     * @param int $per_page
     * @return Paginator
     */
    public function userAssignments(int $user_id, int $per_page = 50): Paginator
    {
        $assignments = new Assignments($per_page, $user_id);

        return $assignments->paginate($this->connector);

    }

    public function update(UpdateAssignment $updateAssignment): void
    {
        //TODO
    }


}