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

namespace Tuxxedo\Temporal;

class TimeZone implements TimeZoneInterface
{
    public string $name {
        get {
            return $this->dateTimeZone->getName();
        }
    }

    private function __construct(
        public readonly \DateTimeZone $dateTimeZone,
    ) {
    }

    public static function parse(
        string $input,
    ): self {
        try {
            return new self(
                dateTimeZone: new \DateTimeZone(timezone: $input),
            );
        } catch (\Exception $exception) {
            throw TemporalException::fromMalformedTimeZone(
                input: $input,
                previous: $exception,
            );
        }
    }

    public static function utc(): self
    {
        return new self(
            dateTimeZone: new \DateTimeZone(timezone: 'UTC'),
        );
    }

    public static function fromDateTimeZone(
        \DateTimeZone $dateTimeZone,
    ): self {
        return new self(
            dateTimeZone: $dateTimeZone,
        );
    }

    public function equals(
        TimeZoneInterface $other,
    ): bool {
        return $this->name === $other->name;
    }
}
