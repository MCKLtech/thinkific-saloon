<?php

namespace WooNinja\ThinkificSaloon\Requests\Instructors;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class Delete extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $instructor_id,
    )
    {

    }

    public function resolveEndpoint(): string
    {
        return "instructors/{$this->instructor_id}";
    }


}