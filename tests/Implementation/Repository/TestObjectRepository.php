<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Repository;

use Doctrine\Common\Collections\Criteria;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Repository\ObjectRepositoryInterface;
use Honey\ODM\Core\Repository\ResultInterface;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

use function array_map;

/**
 * @implements ObjectRepositoryInterface<Criteria>
 */
final readonly class TestObjectRepository implements ObjectRepositoryInterface
{
    public function __construct(
        private ObjectManager $manager,
        private string $className,
    ) {
    }

    public function findAll(): ResultInterface
    {
        return $this->findBy(new Criteria());
    }

    public function findBy(mixed $criteria): ResultInterface
    {
        /** @var TestTransport $transport */
        $transport = $this->manager->transport;
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);
        $documents = $transport->retrieveDocuments($criteria);

        return new TestResult(
            array_map(fn (array $document) => $this->manager->factory($document, $classMetadata), $documents),
        );
    }

    public function findOneBy(mixed $criteria): ?object
    {
        foreach ($this->findBy($criteria->setMaxResults(1)) as $object) {
            return $object;
        }

        return null;
    }

    public function find(mixed $id): ?object
    {
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);

        return $this->manager->factory(
            $this->manager->transport->retrieveDocumentById($classMetadata, $id),
            $classMetadata,
        );
    }
}
