<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments;

final class UpdateAssignment
{
    public function __construct(
        public int                    $assignment_id,
        public Enums\AssignmentStatus $status,
    )
    {
    }
}