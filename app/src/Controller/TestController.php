<?php

namespace App\Controller;

use App\Dto\ComplexObject;
use App\Dto\EmbedObject;
use AutoMapper\AutoMapperInterface;
use phpDocumentor\Reflection\DocBlock\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use function Zenstruck\Foundry\faker;

class TestController extends AbstractController
{
    private function generateEmbedObject(): EmbedObject
    {
        return new EmbedObject(
            'name',
            faker()->numberBetween(1, 100),
            'address'
        );
    }

    #[Route('/api/test', name: 'app_test')]
    public function index(SerializerInterface $serializer, DenormalizerInterface $denormalizer, AutoMapperInterface $autoMapper): JsonResponse
    {
        $objects = [];
        foreach (range(1, 100) as $i) {
            $objects[] = (new ComplexObject())
                ->setName(faker()->name())
                ->setAge(faker()->numberBetween(1, 100))
                ->setAddress(faker()->address())
                ->setEmbedObject($this->generateEmbedObject())
                ->setArrayOfEmbedObjects(array_map(fn() => $this->generateEmbedObject(), range(1, faker()->numberBetween(50, 100))));
        }

        $array = [];
        foreach ($objects as $object) {
            $array[] = $autoMapper->map($object, 'array');
        }

        $newObjects = array_map(fn($item) => $autoMapper->map($item, ComplexObject::class), $array);
//        $newObjects = $denormalizer->denormalize($array, ComplexObject::class . '[]');


        return $this->json($newObjects);
    }
}
