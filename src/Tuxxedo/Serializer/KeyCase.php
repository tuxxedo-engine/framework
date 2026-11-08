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

namespace Tuxxedo\Serializer;

enum KeyCase
{
    case CAMEL;
    case PASCAL;
    case SNAKE;
    case KEBAB;

    public function apply(
        string $name,
    ): string {
        return match ($this) {
            self::CAMEL => \lcfirst($name),
            self::PASCAL => \ucfirst($name),
            self::SNAKE => self::separate($name, '_'),
            self::KEBAB => self::separate($name, '-'),
        };
    }

    private static function separate(
        string $name,
        string $separator,
    ): string {
        return \strtolower(
            (string) \preg_replace(
                pattern: '/(?<=[a-z0-9])([A-Z])/',
                replacement: $separator . '$1',
                subject: $name,
            ),
        );
    }
}
