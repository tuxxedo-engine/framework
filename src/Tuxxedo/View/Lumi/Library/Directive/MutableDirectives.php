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

namespace Tuxxedo\View\Lumi\Library\Directive;

class MutableDirectives extends Directives implements MutableDirectivesInterface
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
