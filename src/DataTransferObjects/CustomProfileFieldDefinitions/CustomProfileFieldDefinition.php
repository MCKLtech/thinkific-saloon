<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\CustomProfileFieldDefinitions;

class CustomProfileFieldDefinition
{
    public function __construct(
        public int    $id,
        public string $label,
        public string $field_type,
        public bool   $required
    )
    {

    }
}