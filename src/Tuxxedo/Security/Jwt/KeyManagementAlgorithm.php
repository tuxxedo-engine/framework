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

namespace Tuxxedo\Security\Jwt;

enum KeyManagementAlgorithm
{
    case DIR;
    case A128KW;
    case A192KW;
    case A256KW;

    public function identifier(): string
    {
        return match ($this) {
            self::DIR => 'dir',
            default => $this->name,
        };
    }

    public function is(
        string $identifier,
    ): bool {
        return \strcasecmp($this->identifier(), $identifier) === 0;
    }
}
