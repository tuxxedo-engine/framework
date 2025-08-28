<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Runtime;

// @todo Throw exceptions for as* methods once exception tree has been fixed
interface LumiDirectivesInterface
{
    public function has(
        string $directive,
    ): bool;

    public function asString(
        string $directive,
    ): string;

    public function asInt(
        string $directive,
    ): int;

    public function asFloat(
        string $directive,
    ): float;

    public function asBool(
        string $directive,
    ): bool;

    public function isNull(
        string $directive,
    ): bool;
}
