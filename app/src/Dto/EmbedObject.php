<?php

declare(strict_types=1);

namespace App\Dto;

class EmbedObject
{
    public function __construct(
        private readonly string $name = '',
        private readonly int $age = 0,
        private readonly string $address = ''
    ) {
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
