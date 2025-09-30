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

use function sprintf;

final class DateTimeImmutableTransformer implements PropertyTransformerInterface
{
    public function fromDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): ?DateTimeImmutable {
        if (null === $value) {
            return null;
        }

        $transformerOptions = $propertyMetadata->transformer?->options ?? [];
        $format = $transformerOptions['from_format'] ?? DateTimeInterface::ATOM;
        $timeZone = $transformerOptions['from_tz'] ?? null;

        return match ($timeZone) {
            null => DateTimeImmutable::createFromFormat($format, (string) $value),
            default => DateTimeImmutable::createFromFormat($format, (string) $value, new DateTimeZone($timeZone)),
        } ?: throw new \RuntimeException("Failed to transform value '$value' to DateTimeImmutable using format '$format'.");
    }

    /**
     * @param DateTime|DateTimeImmutable $value
     * @param PropertyMetadataInterface $propertyMetadata
     * @param MappingContextInterface $context
     * @return string|null
     * @throws DateInvalidTimeZoneException
     */
    public function toDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): string|int|float|null {
        if (null === $value) {
            return null;
        }
        if (!$value instanceof DateTimeInterface) {
            throw new InvalidArgumentException(sprintf("Expected instance of DateTimeInterface, got '%s'.", get_debug_type($value)));
        }

        $transformerOptions = $propertyMetadata->transformer?->options ?? [];
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
