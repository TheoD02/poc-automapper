<?php

namespace App\Dto;

class EmbedObject
{
    private string $name;
    private int $age;
    private string $address;

    public function __construct(string $name, int $age, string $address)
    {
        $this->name = $name;
        $this->age = $age;
        $this->address = $address;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function getAddress(): string
    {
        return $this->address;
    }
}