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

namespace Tuxxedo\Console;

use Tuxxedo\Console\Output\ConsoleOutputInterface;

interface ExitCodeExceptionInterface
{
    public function handleAsError(
        ConsoleOutputInterface $output,
    ): ExitCode;
}
