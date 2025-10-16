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
 * @template TClassMetadata of ClassMetadata
 * @template TPropertyMetadata of PropertyMetadata
 *
 * @implements ClassMetadataRegistryInterface<TClassMetadata, TPropertyMetadata>
 */
// @phpstan-ignore trait.unused
trait ClassMetadataRegistryTrait
{
    /**
     * @var ArrayObject<class-string, ClassMetadata>
     */
    private ArrayObject $storage; // @phpstan-ignore missingType.generics

    /**
     * @template O of object
     *
     * @param array<class-string, ClassMetadata<O, PropertyMetadata>>|list<class-string<O>> $configurations
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
             * @var array<class-string<O>, ClassMetadata<O, PropertyMetadata>> $configurations
             */
            foreach ($configurations as $className => $classMetadata) {
                $this->storage->offsetSet(
                    $className,
                    $this->populateClassMetadata(Reflection::class($className), $classMetadata), // @phpstan-ignore argument.type
                );
            }
        }
    }

    public function getClassMetadata(string $className): ClassMetadata
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
    private function readClassMetadata(string $className): ClassMetadata // @phpstan-ignore missingType.generics, return.unresolvableType
    {
        $classRefl = Reflection::class($className);

        /** @var ClassMetadata<object, PropertyMetadata> $classMetadata */
        $classMetadata = $this->readClassMetadataAttribute($classRefl)->newInstance();

        return $this->populateClassMetadata($classRefl, $classMetadata);
    }

    /**
     * @template O of object
     *
     * @param ReflectionClass<O> $classRefl
     * @param ClassMetadata<object, PropertyMetadata> $classMetadata
     *
     * @return ClassMetadata<object, PropertyMetadata>
     */
    private function populateClassMetadata(
        ReflectionClass $classRefl,
        ClassMetadata $classMetadata,
    ): ClassMetadata {
        $hasPrimary = false;
        $propertiesMetadata = [];
        foreach ($classRefl->getProperties() as $propertyRefl) {
            $reflAttributes = $propertyRefl->getAttributes(
                PropertyMetadata::class,
                ReflectionAttribute::IS_INSTANCEOF,
            );
            if (!isset($reflAttributes[0])) {
                break;
            }
            /** @var PropertyMetadata $propertyMetadata */
            $propertyMetadata = $reflAttributes[0]->newInstance();
            if ($propertyMetadata->primary) {
                $hasPrimary = true;
            }
            $propertiesMetadata[$propertyRefl->name] = $propertyMetadata;
            Reflection::property($propertyMetadata, 'reflection')->setValue($propertyMetadata, $propertyRefl);
            Reflection::property($propertyMetadata, 'classMetadata')->setValue($propertyMetadata, $classMetadata);
        }
        Reflection::property($classMetadata, 'className')->setValue($classMetadata, $classRefl->name);
        Reflection::property($classMetadata, 'reflection')->setValue($classMetadata, $classRefl);
        Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, $propertiesMetadata);

        if (!$hasPrimary) {
            throw self::noPrimaryKeyMapException($classRefl->getName());
        }

        return $classMetadata;
    }

    /**
     * @param ReflectionClass<object> $classRefl
     *
     * @return ReflectionAttribute<ClassMetadata<object, PropertyMetadata>>
     */
    private function readClassMetadataAttribute(ReflectionClass $classRefl): ReflectionAttribute
    {
        /** @var ReflectionAttribute<ClassMetadata<object, PropertyMetadata>>[] $attributes */
        $attributes = $classRefl->getAttributes(ClassMetadata::class, ReflectionAttribute::IS_INSTANCEOF);

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
