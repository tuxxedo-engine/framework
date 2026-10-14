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

namespace Tuxxedo\Intl\Source;

class LocaleSourceRegistry implements LocaleSourceRegistryInterface
{
    /**
     * @var list<array{SourcePriority, LocaleSourceInterface}>
     */
    private array $entries = [];

    /**
     * @var list<LocaleSourceInterface>|null
     */
    private ?array $sorted = null;

    public function register(
        SourcePriority $priority,
        LocaleSourceInterface $source,
    ): void {
        $this->entries[] = [
            $priority,
            $source,
        ];

        $this->sorted = null;
    }

    /**
     * @return list<LocaleSourceInterface>
     */
    public function sortedSources(): array
    {
        if ($this->sorted !== null) {
            return $this->sorted;
        }

        $entries = $this->entries;

        \usort(
            $entries,
            static fn (array $a, array $b): int => $a[0]->value <=> $b[0]->value,
        );

        $sources = [];

        foreach ($entries as $entry) {
            $sources[] = $entry[1];
        }

        $this->sorted = $sources;

        return $sources;
    }
}
