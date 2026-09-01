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

namespace Tuxxedo\Console\Kernel;

use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Middleware\CommandInvocationInterface;

interface CommandDispatcherInterface
{
    /**
     * @param list<string> $argv
     *
     * @throws ConsoleException
     */
    public function resolve(
        array $argv,
    ): CommandInvocationInterface;

    /**
     * @throws ConsoleException
     */
    public function execute(
        CommandInvocationInterface $invocation,
    ): ExitCode;
}
