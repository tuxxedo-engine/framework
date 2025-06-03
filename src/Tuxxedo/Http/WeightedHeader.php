<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Http;

readonly class WeightedHeader extends Header implements WeightedHeaderInterface
{
    public function getWeightedOrder(): array
    {
        $parsed = [];

        foreach (\explode(',', $this->value) as $part) {
            if (\preg_match('/^\s*([^;]+)(?:;\s*q=([0-9.]+))?\s*$/', $part, $matches) !== false) {
                $parsed[] = [
                    $matches[1],
                    isset($matches[2])
                        ? (float) $matches[2]
                        : 1.0,
                ];
            }
        }

        \usort(
            $parsed,
            static fn(array $a, array $b): int => $b[1] <=> $a[1],
        );

        return \array_column($parsed, 0);
    }
}
