<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

/**
 * Marker interface for platform-specific metadata attributes.
 *
 * Implementations of the ODM (Meilisearch, SQL, Elasticsearch, ...) provide their own
 * attributes implementing this interface, to be placed alongside #[AsDocument] / #[AsField].
 * They are collected by the ClassMetadataRegistry and exposed through
 * AsDocument::getPlatformMetadata() / AsField::getPlatformMetadata().
 */
interface PlatformMetadataInterface
{
}
