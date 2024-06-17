<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Requests\Assignments;


use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Assignments\UpdateAssignment;

final class Update extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly UpdateAssignment $updateAssignment
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "";
    }

    protected function defaultBody(): array
    {

        return [
            'query' => 'mutation UpdateAssignmentSubmissionStatus($input: UpdateAssignmentSubmissionStatusInput!) {
  updateAssignmentSubmissionStatus(input: $input) {
    submission {
      id
      status,
      createdAt,
      updatedAt,
      reviewedAt
    }
  }
}',
            'variables' => [
                'input' => [
                    'status' => $this->updateAssignment->status,
                    'submissionId' => $this->updateAssignment->assignment_id
                ]
            ]
        ];
    }

}