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
     * @var list<array{SourcePriority, LocaleSourceInterface|(\Closure(): LocaleSourceInterface)}>
     */
    private array $entries = [];

    /**
     * @var list<LocaleSourceInterface>|null
     */
    private ?array $sorted = null;

    public function register(
        SourcePriority $priority,
        LocaleSourceInterface|\Closure $source,
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

        $entries = [];

        foreach ($this->entries as $index => [$priority, $source]) {
            if ($source instanceof \Closure) {
                $source = $source();

                $this->entries[$index] = [
                    $priority,
                    $source,
                ];
            }

            $entries[] = [
                $priority,
                $source,
            ];
        }

        \usort(
            $entries,
            static fn (array $a, array $b): int => $a[0]->value <=> $b[0]->value,
        );

        $this->sorted = \array_column($entries, 1);

        return $this->sorted;
    }
}
