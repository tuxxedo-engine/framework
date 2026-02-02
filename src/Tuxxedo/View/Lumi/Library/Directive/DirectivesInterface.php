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

namespace Tuxxedo\View\Lumi\Library\Directive;

use Tuxxedo\View\ViewException;

interface DirectivesInterface
{
    /**
     * @var array<string, string|int|float|bool|null>
     */
    public array $directives {
        get;
    }

    public function has(
        string $directive,
    ): bool;

    /**
     * @throws ViewException
     */
    public function asString(
        string $directive,
    ): string;

    /**
     * @throws ViewException
     */
    public function asInt(
        string $directive,
    ): int;

    /**
     * @throws ViewException
     */
    public function asFloat(
        string $directive,
    ): float;

    /**
     * @throws ViewException
     */
    public function asBool(
        string $directive,
    ): bool;

    /**
     * @throws ViewException
     */
    public function isNull(
        string $directive,
    ): bool;
}
