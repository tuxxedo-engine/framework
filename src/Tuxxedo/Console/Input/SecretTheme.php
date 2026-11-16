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

namespace Tuxxedo\Console\Input;

use Tuxxedo\Console\Output\Style\Style;

class SecretTheme implements SecretThemeInterface
{
    public function __construct(
        public readonly string $mask,
        public readonly ?Style $maskStyle = null,
    ) {
    }

    public static function default(): self
    {
        return new self(
            mask: '*',
        );
    }

    public static function hidden(): self
    {
        return new self(
            mask: '',
        );
    }
}
