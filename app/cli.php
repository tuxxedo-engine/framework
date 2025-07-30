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

namespace {
    use Tuxxedo\Console\Console;

    require_once __DIR__ . '/../vendor/autoload.php';

    $console = Console::createFromDirectory(
        directory: __DIR__,
    );

    // @todo Put in same directory as app?
    $console->run($argv);
}
