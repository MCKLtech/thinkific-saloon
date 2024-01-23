<?php

namespace WooNinja\ThinkificSaloon\Traits;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

trait MapperTrait
{
    /**
     * Attempts to map a source DTO to a target DTO
     * 
     * @param $sourceDTO
     * @param $targetDTO
     * @return mixed
     * @throws ReflectionException
     */
    public function mapDTO($sourceDTO, $targetDTO): mixed
    {
        // Get the class properties of the target DTO
        $targetReflectionClass = new ReflectionClass($targetDTO);
        $targetProperties = $targetReflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);

        // Create a new instance of the target DTO
        $values = [];

        // Map properties from source DTO to target DTO
        foreach ($targetProperties as $property) {
            $propertyName = $property->getName();

            // Check if the property exists in the source DTO
            if (property_exists($sourceDTO, $propertyName)) {
                $values[$propertyName] = $sourceDTO->$propertyName;
            }
        }

        return new $targetDTO(...$values);
    }

}