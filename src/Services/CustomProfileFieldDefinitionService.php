<?php

namespace WooNinja\ThinkificSaloon\Services;

use Saloon\PaginationPlugin\PagedPaginator;
use WooNinja\ThinkificSaloon\Requests\CustomProfileFieldDefinitions\CustomProfileFieldDefinition;
use WooNinja\ThinkificSaloon\Requests\Users\Users;

class CustomProfileFieldDefinitionService extends Resource
{

    /**
     * Get the custom profile definitions from Thinkific
     * @see https://developers.thinkific.com/api/api-documentation/#/Custom%20Profile%20Field%20Definitions/getCustomProfileFields
     *
     * @return PagedPaginator
     */
    public function definitions(): PagedPaginator
    {
        return $this->connector
            ->paginate(new CustomProfileFieldDefinition());
    }

    /**
     * Search for users in Thinkific with a given custom profile field label and value
     *
     * @param string $label
     * @param string $value
     * @return PagedPaginator
     */
    public function searchUsers(string $label, string $value) {

        $filters = [
            "query[custom_profile_field_label]" => $label,
            "query[custom_profile_field_value]" => $value,
        ];

        return $this->connector
            ->paginate(new Users($filters));
    }
}