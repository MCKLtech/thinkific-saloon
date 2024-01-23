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
        return array_filter($array, fn($value) => $value !== null);
    }
}