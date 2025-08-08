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
            $segments = \explode(';', $part);
            $value = \trim(\array_shift($segments), " \t\n\r\0\x0B\"");
            $weight = 1.0;

            foreach ($segments as $parameter) {
                $parameter = \trim($parameter);

                if (\str_starts_with($parameter, 'q=')) {
                    $weightedValue = \substr($parameter, 2);

                    if (\is_numeric($weightedValue)) {
                        $weight = (float) $weightedValue;
                    }

                    break;
                }
            }

            $parsed[] = [
                $value,
                $weight,
            ];
        }

        \usort(
            $parsed,
            static fn (array $a, array $b): int => $b[1] <=> $a[1],
        );

        return \array_filter(
            \array_column($parsed, 0),
            static fn (?string $value): bool => $value !== null,
        );
    }

    public function getWeightedPairs(): array
    {
        $parsed = [];

        foreach (\explode(',', $this->value) as $part) {
            $segments = \explode(';', $part);
            $value = \trim(\array_shift($segments), " \t\n\r\0\x0B\"");
            $weight = 1.0;

            foreach ($segments as $parameter) {
                $parameter = \trim($parameter);

                if (\str_starts_with($parameter, 'q=')) {
                    $weightedValue = \substr($parameter, 2);

                    if (\is_numeric($weightedValue)) {
                        $weight = (float) $weightedValue;
                    }

                    break;
                }
            }

            $parsed[] = new WeightedHeaderPair(
                value: $value,
                weight: $weight,
            );
        }

        \usort(
            $parsed,
            static fn (WeightedHeaderPairInterface $a, WeightedHeaderPairInterface $b): int => $b->weight <=> $a->weight,
        );

        return $parsed;
    }
}
