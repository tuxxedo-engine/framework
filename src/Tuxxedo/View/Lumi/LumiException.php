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

namespace Tuxxedo\View\Lumi;

class LumiException extends \Exception
{
    public function __construct(
        string $message,
    ) {
        if (static::class !== self::class) {
            $message = \sprintf(
                '%s: %s',
                static::class,
                $message,
            );
        }

        parent::__construct($message);
    }
}
