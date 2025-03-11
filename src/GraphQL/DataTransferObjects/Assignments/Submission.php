<?php

namespace WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments;

use Carbon\Carbon;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;

final class Submission
{
    public function __construct(
        public int     $id,
        public string  $status,
        public string  $name,
        public string  $type,
        public string  $url,
        public int     $lesson_id,
        public string  $lesson_name,
        public int     $chapter_id,
        public string  $chapter_name,
        public int     $product_id,
        public string  $product_name,
        public ?Carbon $reviewed_at,
        public Carbon  $updated_at,
        public Carbon  $created_at,
        public ?User   $user = null,
    )
    {
    }

    public function canBeUpdated(): bool
    {
        return $this->isPending();
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }
}