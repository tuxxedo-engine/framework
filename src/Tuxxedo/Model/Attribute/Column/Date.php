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

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class Date implements ColumnFormatInterface
{
    public function __construct(
        public DateFormat|string $format = DateFormat::DEFAULT,
        public ?string $name = null,
    ) {
    }

    public function getNativeType(
        DialectInterface $dialect,
    ): string {
        return 'DATE';
    }

    public function getFormat(
        DialectInterface $dialect,
    ): string {
        return $this->format instanceof DateFormat
                ? $this->format->value
                : $this->format;
    }
}
