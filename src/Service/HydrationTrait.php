<?php

namespace App\Service;

trait HydrationTrait
{
    protected function hydrateEntity(array $data, object $entity): object
    {
        foreach ($data as $key => $value) {
            $method = 'set' . $this->camelize($key);
            if (method_exists($entity, $method)) {
                $entity->$method($value);
            }
        }
        return $entity;
    }

    protected function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }
} 