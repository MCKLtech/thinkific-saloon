<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;


use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments\UpdateAssignment;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Assignments\Assignments;


class AssignmentService extends Resource
{
    /**
     * Return a list of Assignments
     *
     * @param int $per_page
     * @return Paginator
     */
    public function assignments(int $per_page = 100): Paginator
    {
        $assignments = new Assignments($per_page);

        return $assignments->paginate($this->connector);

    }

    public function update(UpdateAssignment $updateAssignment): void
    {
        //TODO
    }


}