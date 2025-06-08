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

namespace Tuxxedo\Router;

use Tuxxedo\Collections\FileCollection;

class RouterFactory
{
    final private function __construct()
    {
    }

    public static function createFromDirectory(
        string $directory,
    ): void {
        $controllers = FileCollection::fromFileType(
            directory: $directory,
            extension: 'php',
        );
    }
}
