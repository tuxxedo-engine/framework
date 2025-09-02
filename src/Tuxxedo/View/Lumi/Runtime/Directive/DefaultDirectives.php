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

namespace Tuxxedo\View\Lumi\Runtime\Directive;

class DefaultDirectives
{
    /**
     * @return array<string, string|int|float|bool|null>
     */
    public static function defaults(): array
    {
        return [
            'lumi.autoescape' => true,
            'lumi.compiler_strip_comments' => true,
        ];
    }
}
