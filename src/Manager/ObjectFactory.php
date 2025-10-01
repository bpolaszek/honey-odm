<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;

/**
 * @template D of object|array<string, mixed>
 * @template O of object
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 *
 * @internal
 */
final readonly class ObjectFactory
{
    /**
     * @param ClassMetadataRegistryInterface<D, O, C<O, P>, P> $classMetadataRegistry
     */
    public function __construct(
        private ClassMetadataRegistryInterface $classMetadataRegistry,
        private DocumentMapperInterface $documentMapper,
        private Identities $identities,
    ) {
    }

    /**
     * @param ClassMetadataInterface<O, P> $classMetadata
     */
    public function factory(mixed $document, ClassMetadataInterface $classMetadata): object
    {
        $identityMap = $this->identities;
        $id = $this->classMetadataRegistry->getIdFromDocument($document, $classMetadata->className);
        $className = $classMetadata->className;
        if ($identityMap->containsId($className, $id)) {
            return $identityMap->getObject($className, $id);
        }
        $object = Reflection::class($className)->newLazyProxy(function () use ($document, $className, $classMetadata) {
            return $this->documentMapper->documentToObject(
                $classMetadata,
                $document,
                Reflection::class($className)->newInstanceWithoutConstructor()
            );
        });
        $identityMap->attach($object);
        $identityMap->rememberState($object, $document);

        return $object;
    }
}
