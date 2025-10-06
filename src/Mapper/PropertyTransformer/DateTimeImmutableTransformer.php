<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use DateInvalidTimeZoneException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use InvalidArgumentException;
use RuntimeException;

use function sprintf;

final class DateTimeImmutableTransformer implements PropertyTransformerInterface
{
    /**
     * @param string|int|float|null $value
     */
    public function fromDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): ?DateTimeImmutable {
        if (null === $value) {
            return null;
        }

        /** @var array{from_format?: string, from_tz?: string} $transformerOptions */
        $transformerOptions = $propertyMetadata->getTransformer()->options ?? [];
        $format = $transformerOptions['from_format'] ?? DateTimeInterface::ATOM;
        $timeZone = $transformerOptions['from_tz'] ?? null;

        return match ($timeZone) {
            null => DateTimeImmutable::createFromFormat((string) $format, (string) $value),
            default => DateTimeImmutable::createFromFormat((string) $format, (string) $value, new DateTimeZone((string) $timeZone)),
        } ?: throw new RuntimeException("Failed to transform value '$value' to DateTimeImmutable using format '$format'.");
    }

    /**
     * @param DateTime|DateTimeImmutable|null $value
     *
     * @throws DateInvalidTimeZoneException
     */
    // @phpstan-ignore return.unusedType, return.unusedType
    public function toDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): string|int|float|null {
        if (null === $value) {
            return null;
        }
        if (!$value instanceof DateTimeInterface) { // @phpstan-ignore instanceof.alwaysTrue
            throw new InvalidArgumentException(sprintf("Expected instance of DateTimeInterface, got '%s'.", get_debug_type($value)));
        }

        /** @var array{to_format?: string, to_tz?: string, to_type?: string} $transformerOptions */
        $transformerOptions = $propertyMetadata->getTransformer()->options ?? [];
        $format = $transformerOptions['to_format'] ?? DateTimeInterface::ATOM;
        $timeZone = $transformerOptions['to_tz'] ?? null;
        $cast = $transformerOptions['to_type'] ?? null;

        $output = match ($timeZone) {
            null => $value->format($format),
            default => $value->setTimezone(new DateTimeZone($timeZone))->format($format),
        };

        if ($cast) {
            settype($output, $cast);
        }

        return $output;
    }
}
