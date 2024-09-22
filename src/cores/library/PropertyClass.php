<?php

namespace cores\library;

use ArrayAccess;

class PropertyClass implements ArrayAccess
{
    private array $properties = [];

    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
    }

    public function __get(string $name)
    {
        return $this->properties[$name] ?? null;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->properties[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->properties[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->properties[] = $value;
            return;
        }
        $this->properties[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }
}