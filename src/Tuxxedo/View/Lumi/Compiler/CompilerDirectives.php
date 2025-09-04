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

namespace Tuxxedo\View\Lumi\Compiler;

use Tuxxedo\View\Lumi\Runtime\Directive\DefaultDirectives;
use Tuxxedo\View\Lumi\Runtime\Directive\Directives;

class CompilerDirectives extends Directives implements CompilerDirectivesInterface
{
    public function set(
        string $directive,
        string|int|float|bool|null $value,
    ): void {
        $this->directives[$directive] = $value;
    }

    public static function createWithDefaults(): self
    {
        return new self(
            directives: DefaultDirectives::defaults(),
        );
    }
}
