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

interface HeaderInterface
{
    public string $algorithm {
        get;
    }

    public ?string $type {
        get;
    }

    public ?string $keyId {
        get;
    }

    /**
     * @var array<string, mixed>
     */
    public array $all {
        get;
    }

    public function has(
        string $header,
    ): bool;

    public function get(
        string $header,
    ): mixed;
}
