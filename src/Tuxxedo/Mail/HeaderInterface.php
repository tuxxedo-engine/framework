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

namespace Tuxxedo\Mail;

interface HeaderInterface
{
    public string $name {
        get;
    }

    public string $value {
        get;
    }

    public function is(
        string $name,
    ): bool;

    #[\NoDiscard]
    public function withValue(
        string $value,
    ): static;

    #[\NoDiscard]
    public function toRfc5322(): string;
}
