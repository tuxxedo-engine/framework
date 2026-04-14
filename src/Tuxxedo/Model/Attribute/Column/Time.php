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
use Tuxxedo\Model\Attribute\ColumnFormatInterface;
use Tuxxedo\Model\Attribute\ColumnInterface;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class Time implements ColumnInterface, ColumnFormatInterface
{
    public function __construct(
        public TimeFormat|string $format = TimeFormat::DEFAULT,
        public ?string $name = null,
    ) {
    }

    public function getNativeType(
        DialectInterface $dialect,
    ): string {
        return $dialect->nativeColumnType($this) ?? 'TIME';
    }

    public function getFormat(
        DialectInterface $dialect,
    ): string {
        return $this->format instanceof TimeFormat
            ? $this->format->value
            : $this->format;
    }
}
