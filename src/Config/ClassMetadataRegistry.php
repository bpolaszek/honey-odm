<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use ArrayObject;
use BenTools\ReflectionPlus\Reflection;
use InvalidArgumentException;
use IteratorAggregate;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Traversable;

use function array_map;
use function Honey\ODM\Core\throws;
use function sprintf;

/**
 * @implements IteratorAggregate<class-string, AsDocument<object>>
 */
final class ClassMetadataRegistry implements ClassMetadataRegistryInterface, IteratorAggregate
{
    /**
     * @var ArrayObject<class-string, AsDocument<object>>
     */
    private ArrayObject $storage;

    /**
     * Configurations can be a list of document class names to warm up,
     * or a map of class names to externally-provided AsDocument instances
     * (useful for classes that cannot be annotated, e.g. third-party classes).
     *
     * @param array<class-string, AsDocument<object>>|list<class-string> $configurations
     */
    public function __construct(
        public readonly PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
        array $configurations = [],
    ) {
        $this->storage = new ArrayObject();
        if (array_is_list($configurations)) {
            /** @var list<class-string> $configurations */
            foreach ($configurations as $className) {
                $this->getClassMetadata($className);
            }
        } else {
            /** @var array<class-string, AsDocument<object>> $configurations */
            foreach ($configurations as $className => $classMetadata) {
                $this->storage->offsetSet(
                    $className,
                    $this->populateClassMetadata(Reflection::class($className), $classMetadata),
                );
            }
        }
    }

    public function getClassMetadata(string $className): AsDocument
    {
        if (!$this->storage->offsetExists($className)) {
            $this->storage->offsetSet($className, $this->readClassMetadata($className));
        }

        return $this->storage->offsetGet($className); // @phpstan-ignore return.type
    }

    public function hasClassMetadata(string $className): bool
    {
        return isset($this->storage[$className])
            || !throws(fn () => $this->readClassMetadata($className));
    }

    public function getIdFromObject(object $object): mixed
    {
        $classMetadata = $this->getClassMetadata($object::class);
        $propertyName = $classMetadata->getIdPropertyMetadata()->reflection->name;

        return $this->propertyAccessor->getValue($object, $propertyName);
    }

    public function getIdFromDocument(array $document, string $className): mixed
    {
        $classMetadata = $this->getClassMetadata($className);
        $fieldName = $classMetadata->getIdPropertyMetadata()->fieldName;

        return $this->propertyAccessor->getValue((object) $document, $fieldName);
    }

    public function getIterator(): Traversable
    {
        yield from $this->storage;
    }

    /**
     * @template O of object
     *
     * @param class-string<O> $className
     *
     * @return AsDocument<O>
     */
    private function readClassMetadata(string $className): AsDocument
    {
        $classRefl = Reflection::class($className);
        $reflAttributes = $classRefl->getAttributes(AsDocument::class);
        $classMetadata = ($reflAttributes[0] ?? throw self::noMetadataException($className))->newInstance();

        return $this->populateClassMetadata($classRefl, $classMetadata); // @phpstan-ignore return.type
    }

    /**
     * @template O of object
     *
     * @param ReflectionClass<O> $classRefl
     * @param AsDocument<O> $classMetadata
     *
     * @return AsDocument<O>
     */
    private function populateClassMetadata(ReflectionClass $classRefl, AsDocument $classMetadata): AsDocument
    {
        $hasPrimary = false;
        $propertiesMetadata = [];
        foreach ($classRefl->getProperties() as $propertyRefl) {
            $reflAttributes = $propertyRefl->getAttributes(AsField::class);
            if (!isset($reflAttributes[0])) {
                continue;
            }
            $propertyMetadata = $reflAttributes[0]->newInstance();
            if ($propertyMetadata->primary) {
                $hasPrimary = true;
            }
            $propertiesMetadata[$propertyRefl->name] = $propertyMetadata;
            Reflection::property($propertyMetadata, 'reflection')->setValue($propertyMetadata, $propertyRefl);
            Reflection::property($propertyMetadata, 'classMetadata')->setValue($propertyMetadata, $classMetadata);
            Reflection::property($propertyMetadata, 'platformMetadata')->setValue(
                $propertyMetadata,
                self::readPlatformMetadata($propertyRefl->getAttributes(PlatformMetadataInterface::class, ReflectionAttribute::IS_INSTANCEOF)),
            );
        }
        Reflection::property($classMetadata, 'className')->setValue($classMetadata, $classRefl->name);
        Reflection::property($classMetadata, 'reflection')->setValue($classMetadata, $classRefl);
        Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, $propertiesMetadata);
        Reflection::property($classMetadata, 'platformMetadata')->setValue(
            $classMetadata,
            self::readPlatformMetadata($classRefl->getAttributes(PlatformMetadataInterface::class, ReflectionAttribute::IS_INSTANCEOF)),
        );

        if (!$hasPrimary) {
            throw self::noPrimaryKeyMapException($classRefl->getName());
        }

        return $classMetadata;
    }

    /**
     * @param list<ReflectionAttribute<PlatformMetadataInterface>> $reflAttributes
     *
     * @return list<PlatformMetadataInterface>
     */
    private static function readPlatformMetadata(array $reflAttributes): array
    {
        return array_map(
            fn (ReflectionAttribute $reflAttribute) => $reflAttribute->newInstance(),
            $reflAttributes,
        );
    }

    /**
     * @codeCoverageIgnore
     */
    private static function noMetadataException(string $className): InvalidArgumentException
    {
        return new InvalidArgumentException(
            sprintf('Class %s is not registered as a Document.', $className),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    private static function noPrimaryKeyMapException(string $className): RuntimeException
    {
        return new RuntimeException(
            sprintf('Class %s has no property mapped as primary key.', $className),
        );
    }
}
