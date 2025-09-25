<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

/**
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 */
interface ClassMetadataRegistryInterface
{
    public function hasClassMetadata(string $className): bool;

    /**
     * @return C<P>
     */
    public function getClassMetadata(string $className): ClassMetadataInterface;
}
