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

namespace Tuxxedo\View\Lumi\Runtime\Introspector;

readonly class RuntimeIntrospector implements RuntimeIntrospectorInterface
{
    /**
     * @var array<string, CallableMetadataInterface>
     */
    private array $functionLookup;

    /**
     * @var array<string, CallableMetadataInterface>
     */
    private array $filterLookup;

    /**
     * @param list<CallableMetadataInterface> $functions
     * @param list<CallableMetadataInterface> $filters
     */
    public function __construct(
        array $functions = [],
        array $filters = [],
    ) {
        $this->functionLookup = self::buildLookup($functions);
        $this->filterLookup = self::buildLookup($filters);
    }

    public function hasFunction(
        string $name,
    ): bool {
        return isset($this->functionLookup[\strtolower($name)]);
    }

    public function getFunction(
        string $name,
    ): ?CallableMetadataInterface {
        return $this->functionLookup[\strtolower($name)] ?? null;
    }

    public function hasFilter(
        string $name,
    ): bool {
        return isset($this->filterLookup[\strtolower($name)]);
    }

    public function getFilter(
        string $name,
    ): ?CallableMetadataInterface {
        return $this->filterLookup[\strtolower($name)] ?? null;
    }

    /**
     * @param list<CallableMetadataInterface> $metadata
     * @return array<string, CallableMetadataInterface>
     */
    private static function buildLookup(
        array $metadata,
    ): array {
        $lookup = [];

        foreach ($metadata as $entry) {
            $lookup[\strtolower($entry->name)] = $entry;

            foreach ($entry->aliases as $alias) {
                $lookup[\strtolower($alias)] = $entry;
            }
        }

        return $lookup;
    }
}
