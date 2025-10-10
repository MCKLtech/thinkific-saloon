<?php

namespace WooNinja\ThinkificSaloon\Traits;

trait RequestTrait
{
    /**
     * Used to remove null or empty value from the body of requests
     *
     * @param array $array
     * @return array
     */
    public function removeEmptyArrayValues(array $array): array
    {
        /**
         * Remove empty values from custom_profile_fields as it can cause requests to fail
         */
        if (isset($array['custom_profile_fields']) && is_array($array['custom_profile_fields'])) {
            $array['custom_profile_fields'] = array_filter($array['custom_profile_fields'], function ($field) {
                return !empty($field['value']) && !empty($field['custom_profile_field_definition_id']);
            });

            // Update/Create should only contain "value" and "custom_profile_field_definition_id"
            $array['custom_profile_fields'] = array_map(function ($field) {
                return [
                    'value' => $field['value'] ?? '',
                    'custom_profile_field_definition_id' => $field['custom_profile_field_definition_id'] ?? '',
                ];
            }, $array['custom_profile_fields']);
        }

        return array_filter($array, fn($value) => $value !== null);
    }
}