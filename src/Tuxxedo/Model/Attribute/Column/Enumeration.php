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
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Model\Hydrator\Coercer\EnumCoercer;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class Enumeration implements ColumnInterface, ColumnEnumInterface
{
    /**
     * @param class-string<\UnitEnum> $enum
     * @param class-string<CoercerInterface>|null $coercer
     */
    public function __construct(
        public string $enum,
        public ?string $name = null,
        public ?string $coercer = EnumCoercer::class,
    ) {
    }

    public function getNativeType(
        DialectInterface $dialect,
    ): string {
        return $dialect->nativeColumnType($this) ?? \sprintf(
            'ENUM(%s)',
            \join(
                ', ',
                \array_map(
                    static fn (\UnitEnum $case): string => \sprintf(
                        "'%s'",
                        \str_replace(
                            "'",
                            "''",
                            $case instanceof \BackedEnum
                                ? (string) $case->value
                                : $case->name,
                        ),
                    ),
                    $this->enum::cases(),
                ),
            ),
        );
    }
}
