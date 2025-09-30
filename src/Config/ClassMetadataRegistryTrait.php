<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use ArrayObject;
use BenTools\ReflectionPlus\Reflection;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use function Honey\ODM\Core\throws;

/**
 * @template O of object
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 *
 * @implements ClassMetadataRegistryInterface<O, C, P>
 */
trait ClassMetadataRegistryTrait
{
    /**
     * @var ArrayObject<class-string, C<O, P>>
     */
    private ArrayObject $storage;

    /**
     * @param array<class-string, C<O, P>>|list<class-string> $configurations
     */
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
        array $configurations = [],
    ) {
        $this->storage = new ArrayObject();
        if (array_is_list($configurations)) {
            /**
             * @var list<class-string> $configurations
             */
            foreach ($configurations as $className) {
                $this->getClassMetadata($className);
            }
        } else {
            /**
             * @var array<class-string, C<O, P>> $configurations
             */
            foreach ($configurations as $className => $classMetadata) {
                $this->storage->offsetSet(
                    $className,
                    $this->populateClassMetadata(Reflection::class($className), $classMetadata),
                );
            }
        }
    }

    /**
     * @param class-string<O> $className
     *
     * @return C<O, P>
     */
    public function getClassMetadata(string $className): ClassMetadataInterface
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

    /**
     * @param class-string<O> $className
     *
     * @return C<O, P>
     */
    private function readClassMetadata(string $className): ClassMetadataInterface
    {
        $classRefl = Reflection::class($className);

        /** @var C<O, P> $classMetadata */
        $classMetadata = $this->readClassMetadataAttribute($classRefl)->newInstance();

        return $this->populateClassMetadata($classRefl, $classMetadata);
    }

    /**
     * @param ReflectionClass<O> $classRefl
     * @param C<O, P> $classMetadata
     *
     * @return C<O, P>
     */
    private function populateClassMetadata(
        ReflectionClass $classRefl,
        ClassMetadataInterface $classMetadata,
    ): ClassMetadataInterface {
        $classMetadata->reflection = $classRefl;
        $classMetadata->className = $classRefl->name;
        $hasPrimary = false;
        foreach ($classRefl->getProperties() as $propertyRefl) {
            $reflAttributes = $propertyRefl->getAttributes(
                PropertyMetadataInterface::class,
                ReflectionAttribute::IS_INSTANCEOF,
            );
            if (!isset($reflAttributes[0])) {
                break;
            }
            /** @var P $propertyMetadata */
            $propertyMetadata = $reflAttributes[0]->newInstance();
            $propertyMetadata->reflection = $propertyRefl;
            $propertyMetadata->classMetadata = $classMetadata;
            if ($propertyMetadata->primary) {
                $hasPrimary = true;
            }
            $classMetadata->propertiesMetadata[$propertyRefl->name] = $propertyMetadata;
        }

        if (!$hasPrimary) {
            throw self::noPrimaryKeyMapException($classRefl->getName());
        }

        return $classMetadata;
    }

    /**
     * @param ReflectionClass<O> $classRefl
     *
     * @return ReflectionAttribute<ClassMetadataInterface<O, P>>
     */
    private function readClassMetadataAttribute(ReflectionClass $classRefl): ReflectionAttribute
    {
        /** @var ReflectionAttribute<ClassMetadataInterface<O, P>>[] $attributes */
        $attributes = $classRefl->getAttributes(ClassMetadataInterface::class, ReflectionAttribute::IS_INSTANCEOF);

        return $attributes[0]
            ?? throw self::noMetadataException($classRefl->getName());
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
