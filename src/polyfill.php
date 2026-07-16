<?php

// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

declare(strict_types=1);

// @codeCoverageIgnoreStart
if (!enum_exists(SortDirection::class)) {
    /**
     * Polyfill for the SortDirection enum introduced in PHP 8.6.
     *
     * @see https://php.watch/versions/8.6/SortDirection
     */
    enum SortDirection
    {
        case Ascending;
        case Descending;
    }
}
// @codeCoverageIgnoreEnd
