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
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Model\Hydrator\Coercer\DateCoercer;

// @todo $format type mismatch across Date/DateTime/Time/Timestamp — attributes accept DateFormat|string (TimeFormat|string for Time) but the corresponding coercers (DateCoercer/DateTimeCoercer/TimeCoercer/TimestampCoercer) accept only the enum; the string side now flows through coercerArguments and breaks Container::resolve. Decide whether to drop |string from the attribute properties (and the CreatedAt/UpdatedAt/DeletedAt parent::__construct signatures) or wire strings end-to-end through the coercer hierarchy.
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class Date implements ColumnInterface, ColumnFormatInterface
{
    /**
     * @var array<string, mixed>
     */
    public array $coercerArguments;

    /**
     * @param class-string<CoercerInterface>|null $coercer
     * @param class-string<BehaviorInterface>|null $behavior
     */
    public function __construct(
        public DateFormat|string $format = DateFormat::DEFAULT,
        public ?string $name = null,
        public ?string $coercer = DateCoercer::class,
        public ?string $behavior = null,
    ) {
        $this->coercerArguments = [
            'format' => $this->format,
        ];
    }

    public function getNativeType(
        DialectInterface $dialect,
    ): string {
        return $dialect->nativeColumnType($this) ?? 'DATE';
    }

    public function getFormat(
        DialectInterface $dialect,
    ): string {
        return $this->format instanceof DateFormat
                ? $this->format->value
                : $this->format;
    }
}
