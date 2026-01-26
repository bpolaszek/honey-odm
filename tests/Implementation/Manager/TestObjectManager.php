<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Manager;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Repository\ObjectRepositoryInterface;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Repository\TestObjectRepository;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;
use Honey\ODM\Core\Transport\TransportInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @implements ObjectManager<TestAsDocument, TestAsField>
 */
final class TestObjectManager extends ObjectManager
{
    public function __construct(
        ClassMetadataRegistryInterface $classMetadataRegistry = new TestClassMetadataRegistry(),
        DocumentMapperInterface $documentMapper = new TestDocumentMapper(),
        EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
        TransportInterface $transport = new TestTransport(),
        array $defaultFlushOptions = [],
    ) {
        parent::__construct(
            $classMetadataRegistry,
            $documentMapper,
            $eventDispatcher,
            $transport,
            $defaultFlushOptions,
        );
    }

    public function getRepository(string $className): ObjectRepositoryInterface
    {
        return $this->repositories[$className]
            ??= $this->registerRepository($className, new TestObjectRepository($this, $className));
    }
}
