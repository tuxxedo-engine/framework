<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Config;

use Tuxxedo\Version;
use Tuxxedo\View\Lumi\LumiConfiguratorInterface;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadataInterface;

class LumiHashBuilder
{
    private const string ALGORITHM = 'xxh128';
    private const string SEPARATOR = "\0";
    private const string ITEM = "\x01";
    private const string GROUP = "\x1E";

    /**
     * @param list<CallableMetadataInterface> $filterMetadata
     * @param list<CallableMetadataInterface> $functionMetadata
     */
    public static function fromConfigurator(
        LumiConfiguratorInterface $configurator,
        array $filterMetadata,
        array $functionMetadata,
    ): string {
        $ctx = \hash_init(self::ALGORITHM);

        \hash_update($ctx, (string) Version::ID);
        \hash_update($ctx, self::GROUP);

        \hash_update($ctx, $configurator->functionPolicy->name);
        \hash_update($ctx, self::GROUP);

        foreach ($configurator->filterClasses as $className) {
            \hash_update($ctx, $className);
            \hash_update($ctx, self::SEPARATOR);
        }

        \hash_update($ctx, self::GROUP);

        foreach ($configurator->functionClasses as $className) {
            \hash_update($ctx, $className);
            \hash_update($ctx, self::SEPARATOR);
        }

        \hash_update($ctx, self::GROUP);

        foreach ($configurator->optimizers as $optimizer) {
            \hash_update($ctx, $optimizer::class);
            \hash_update($ctx, self::SEPARATOR);
        }

        \hash_update($ctx, self::GROUP);

        foreach ($configurator->phpFunctions as $key => $handler) {
            \hash_update($ctx, $key);
            \hash_update($ctx, self::SEPARATOR);
            \hash_update($ctx, $handler->name);
            \hash_update($ctx, self::SEPARATOR);
            \hash_update($ctx, $handler->mappedName ?? '');
            \hash_update($ctx, self::SEPARATOR);

            foreach ($handler->aliases as $alias) {
                \hash_update($ctx, $alias);
                \hash_update($ctx, self::ITEM);
            }

            \hash_update($ctx, self::SEPARATOR);
        }

        \hash_update($ctx, self::GROUP);

        $directives = \array_merge(
            $configurator->defaultDirectives,
            $configurator->directives,
        );
        \ksort($directives);

        foreach ($directives as $key => $value) {
            \hash_update($ctx, $key);
            \hash_update($ctx, self::SEPARATOR);
            \hash_update($ctx, \var_export($value, true));
            \hash_update($ctx, self::SEPARATOR);
        }

        \hash_update($ctx, self::GROUP);

        $themes = $configurator->highlightThemes;
        \ksort($themes);

        foreach ($themes as $name => $theme) {
            \hash_update($ctx, $name);
            \hash_update($ctx, self::SEPARATOR);
            \hash_update($ctx, $theme::class);
            \hash_update($ctx, self::SEPARATOR);
        }

        \hash_update($ctx, self::GROUP);

        self::updateMetadata($ctx, 'F', $filterMetadata);
        \hash_update($ctx, self::GROUP);

        self::updateMetadata($ctx, 'f', $functionMetadata);
        \hash_update($ctx, self::GROUP);

        return \hash_final($ctx);
    }

    public static function fromConfiguratorStandalone(
        LumiConfiguratorInterface $configurator,
    ): string {
        $filterMetadata = [];
        $functionMetadata = [];

        foreach ($configurator->filterClasses as $className) {
            foreach (CallableDiscoverer::discoverFilters($className) as $entry) {
                $filterMetadata[] = $entry;
            }
        }

        foreach ($configurator->functionClasses as $className) {
            foreach (CallableDiscoverer::discoverFunctions($className) as $entry) {
                $functionMetadata[] = $entry;
            }
        }

        return self::fromConfigurator(
            configurator: $configurator,
            filterMetadata: $filterMetadata,
            functionMetadata: $functionMetadata,
        );
    }

    /**
     * @param list<CallableMetadataInterface> $metadata
     */
    private static function updateMetadata(
        \HashContext $ctx,
        string $prefix,
        array $metadata,
    ): void {
        foreach ($metadata as $entry) {
            \hash_update($ctx, $prefix);
            \hash_update($ctx, self::SEPARATOR);
            \hash_update($ctx, $entry->name);
            \hash_update($ctx, self::SEPARATOR);
            \hash_update($ctx, $entry->className);
            \hash_update($ctx, self::SEPARATOR);
            \hash_update($ctx, $entry->methodName);
            \hash_update($ctx, self::SEPARATOR);
            \hash_update(
                $ctx,
                $entry->contextParameterIndex !== null
                    ? (string) $entry->contextParameterIndex
                    : '-',
            );

            \hash_update($ctx, self::SEPARATOR);

            foreach ($entry->aliases as $alias) {
                \hash_update($ctx, $alias);
                \hash_update($ctx, self::ITEM);
            }

            \hash_update($ctx, self::SEPARATOR);
        }
    }
}
