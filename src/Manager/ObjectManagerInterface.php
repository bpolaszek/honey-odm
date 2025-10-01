<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 */
interface ObjectManagerInterface
{
    public ClassMetadataRegistryInterface $classMetadataRegistry {get; }
    public DocumentMapperInterface $documentMapper {get; }
    public EventDispatcherInterface $eventDispatcher {get; }
    public Identities $identities {get; }

    public function persist(object $object, object ...$objects): void;

    public function remove(object $object, object ...$objects): void;

    public function flush(): void;

    /**
     * @template O of object
     *
     * @param class-string<O> $className
     *
     * @return O|null
     */
    public function find(string $className, mixed $id): ?object;
}
