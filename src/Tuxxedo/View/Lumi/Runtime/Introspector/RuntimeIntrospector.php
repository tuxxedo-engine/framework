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
    ): CallableMetadataInterface {
        $key = \strtolower($name);

        if (!isset($this->functionLookup[$key])) {
            throw IntrospectorException::fromUnknownFunction(
                name: $name,
            );
        }

        return $this->functionLookup[$key];
    }

    public function hasAnyFunctions(): bool
    {
        return \sizeof($this->functionLookup) > 0;
    }

    public function hasFilter(
        string $name,
    ): bool {
        return isset($this->filterLookup[\strtolower($name)]);
    }

    public function getFilter(
        string $name,
    ): CallableMetadataInterface {
        $key = \strtolower($name);

        if (!isset($this->filterLookup[$key])) {
            throw IntrospectorException::fromUnknownFilter(
                name: $name,
            );
        }

        return $this->filterLookup[$key];
    }

    public function hasAnyFilters(): bool
    {
        return \sizeof($this->filterLookup) > 0;
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
