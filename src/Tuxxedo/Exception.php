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

namespace Tuxxedo;

class Exception extends \Exception
{
    protected static function formatShortClass(
        string $fqcn,
    ): string {
        $position = \strrpos($fqcn, '\\');

        if ($position === false) {
            return $fqcn;
        }

        return \substr($fqcn, $position + 1);
    }

    protected static function formatQuoted(
        string $value,
    ): string {
        return '"' . $value . '"';
    }
}
