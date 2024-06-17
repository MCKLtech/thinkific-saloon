<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments\Enums;

enum AssignmentStatus: string
{
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case PENDING = 'PENDING';
}