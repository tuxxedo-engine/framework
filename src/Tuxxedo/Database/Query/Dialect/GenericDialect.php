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

namespace Tuxxedo\Database\Query\Dialect;

class GenericDialect implements DialectInterface
{
    public private(set) array $quotations = [
        '\'',
        '"',
    ];

    public private(set) string $booleanType = 'BOOLEAN';
    public private(set) string $jsonType = 'TEXT';

    public function placeholder(
        int $position,
    ): string {
        return '?';
    }

    public function identifier(
        string $name,
    ): string {
        return '"' . \str_replace('"', '""', $name) . '"';
    }

    public function qualifiedIdentifier(
        string $name,
    ): string {
        return \join(
            '.',
            \array_map(
                fn (string $segment): string => $this->identifier($segment),
                \explode('.', $name),
            ),
        );
    }
}
