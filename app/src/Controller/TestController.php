<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ComplexObject;
use App\Dto\EmbedObject;
use AutoMapper\AutoMapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

use function Zenstruck\Foundry\faker;

class TestController extends AbstractController
{
    private function generateEmbedObject(): EmbedObject
    {
        return new EmbedObject('name', faker()->numberBetween(1, 100), 'address');
    }

    #[Route('/api/test', name: 'app_test', methods: [Request::METHOD_GET])]
    public function index(AutoMapperInterface $autoMapper): JsonResponse
    {
        /** @var array<ComplexObject> $objects */
        $objects = [];
        foreach (range(1, 50) as $i) {
            $objects[] = (new ComplexObject())
                ->setName(faker()->name())
                ->setAge(faker()->numberBetween(1, 100))
                ->setAddress(faker()->address())
                ->setEmbedObject($this->generateEmbedObject())
                ->setArrayOfEmbedObjects(array_map(fn (): EmbedObject => $this->generateEmbedObject(), range(1, faker()->numberBetween(50, 100))))
            ;
        }

        /**
         * @template EmbedObjectArrayShape as array{name: string, age: int, address: string}
         * @template ComplexObjectArrayShape as array{name: string, age: int, address: string, embedObject: EmbedObjectArrayShape, arrayOfEmbedObjects: array<EmbedObjectArrayShape>}
         */
        $array = [];
        foreach ($objects as $object) {
            $item = $autoMapper->map($object, 'array');

            if ($item === null) {
                throw new \RuntimeException('Error mapping object');
            }

            $array[] = $item;
        }

        $newObjects = array_map(static fn (array $item): ?ComplexObject => $autoMapper->map($item, ComplexObject::class), $array);

        return $this->json($newObjects);
    }

    #[Route('/api/test', name: 'app_test_2', methods: [Request::METHOD_POST])]
    public function post(#[MapRequestPayload] ComplexObject $complexObject): JsonResponse
    {
        return $this->json($complexObject);
    }
}
