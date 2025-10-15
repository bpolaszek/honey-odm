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
 * @template TClassMetadata of ClassMetadataInterface
 * @template TPropertyMetadata of PropertyMetadataInterface
 *
 * @implements ClassMetadataRegistryInterface<TClassMetadata, TPropertyMetadata>
 */
// @phpstan-ignore trait.unused
trait ClassMetadataRegistryTrait
{
    /**
     * @var ArrayObject<class-string, ClassMetadataInterface>
     */
    private ArrayObject $storage; // @phpstan-ignore missingType.generics

    /**
     * @template O of object
     *
     * @param array<class-string, ClassMetadataInterface<O, PropertyMetadataInterface>>|list<class-string<O>> $configurations
     */
    public function __construct(
        public readonly PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
        array $configurations = [],
    ) {
        $this->storage = new ArrayObject();
        if (array_is_list($configurations)) {
            /**
             * @var list<class-string<O>> $configurations
             */
            foreach ($configurations as $className) {
                $this->getClassMetadata($className);
            }
        } else {
            /**
             * @var array<class-string<O>, ClassMetadataInterface<O, PropertyMetadataInterface>> $configurations
             */
            foreach ($configurations as $className => $classMetadata) {
                $this->storage->offsetSet(
                    $className,
                    $this->populateClassMetadata(Reflection::class($className), $classMetadata), // @phpstan-ignore argument.type
                );
            }
        }
    }

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
     * @template O of object
     *
     * @param class-string<O> $className
     *
     * @return TClassMetadata<O, TPropertyMetadata>
     */
    private function readClassMetadata(string $className): ClassMetadataInterface // @phpstan-ignore missingType.generics, return.unresolvableType
    {
        $classRefl = Reflection::class($className);

        /** @var ClassMetadataInterface<object, PropertyMetadataInterface> $classMetadata */
        $classMetadata = $this->readClassMetadataAttribute($classRefl)->newInstance();

        return $this->populateClassMetadata($classRefl, $classMetadata);
    }

    /**
     * @template O of object
     *
     * @param ReflectionClass<O> $classRefl
     * @param ClassMetadataInterface<object, PropertyMetadataInterface> $classMetadata
     *
     * @return ClassMetadataInterface<object, PropertyMetadataInterface>
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
            /** @var PropertyMetadataInterface $propertyMetadata */
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
     * @param ReflectionClass<object> $classRefl
     *
     * @return ReflectionAttribute<ClassMetadataInterface<object, PropertyMetadataInterface>>
     */
    private function readClassMetadataAttribute(ReflectionClass $classRefl): ReflectionAttribute
    {
        /** @var ReflectionAttribute<ClassMetadataInterface<object, PropertyMetadataInterface>>[] $attributes */
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
