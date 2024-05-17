<?php

declare(strict_types=1);

namespace App\Dto;

class ComplexObject
{
    private string $name = '';

    private int $age = 0;

    private string $address = '';

    private EmbedObject $embedObject;

    /**
     * @var array<EmbedObject>
     */
    private array $arrayOfEmbedObjects = [];

    public function __construct()
    {
        $this->embedObject = new EmbedObject();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getEmbedObject(): EmbedObject
    {
        return $this->embedObject;
    }

    public function setEmbedObject(EmbedObject $embedObject): self
    {
        $this->embedObject = $embedObject;

        return $this;
    }

    /**
     * @return array<EmbedObject>
     */
    public function getArrayOfEmbedObjects(): array
    {
        return $this->arrayOfEmbedObjects;
    }

    /**
     * @param array<EmbedObject> $arrayOfEmbedObjects
     */
    public function setArrayOfEmbedObjects(array $arrayOfEmbedObjects): self
    {
        $this->arrayOfEmbedObjects = $arrayOfEmbedObjects;

        return $this;
    }
}
