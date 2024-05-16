<?php

namespace App\Dto;

class ComplexObject
{
    private string $name;
    private int $age;
    private string $address;

    private EmbedObject $embedObject;

    /**
     * @var array<EmbedObject>
     */
    private array $arrayOfEmbedObjects;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): ComplexObject
    {
        $this->name = $name;
        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): ComplexObject
    {
        $this->age = $age;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): ComplexObject
    {
        $this->address = $address;
        return $this;
    }

    public function getEmbedObject(): EmbedObject
    {
        return $this->embedObject;
    }

    public function setEmbedObject(EmbedObject $embedObject): ComplexObject
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
    public function setArrayOfEmbedObjects(array $arrayOfEmbedObjects): ComplexObject
    {
        $this->arrayOfEmbedObjects = $arrayOfEmbedObjects;
        return $this;
    }
}