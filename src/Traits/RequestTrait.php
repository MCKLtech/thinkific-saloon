<?php

namespace WooNinja\ThinkificSaloon\Traits;

trait RequestTrait
{
    public function removeEmptyArrayValues(array $array): array
    {
        return array_filter($array, fn($value) => $value !== null);
    }
}