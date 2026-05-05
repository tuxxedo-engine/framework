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

namespace Tuxxedo\Model\Attribute\Column;

use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Model\Attribute\ColumnEnumInterface;
use Tuxxedo\Model\Attribute\ColumnInterface;

// @todo Can't $enum be implicitly discovered via Reflection, like some container resolvers do?
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class Enumeration implements ColumnInterface, ColumnEnumInterface
{
    /**
     * @param class-string<\BackedEnum> $enum
     */
    public function __construct(
        public string $enum,
        public ?string $name = null,
    ) {
    }

    public function getNativeType(
        DialectInterface $dialect,
    ): string {
        return $dialect->nativeColumnType($this) ?? \sprintf(
            'ENUM(%s)',
            \implode(
                ', ',
                \array_map(
                    static fn (\BackedEnum $case): string => \sprintf(
                        "'%s'",
                        \str_replace("'", "''", (string) $case->value),
                    ),
                    $this->enum::cases(),
                ),
            ),
        );
    }
}
