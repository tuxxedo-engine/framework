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

namespace Tuxxedo\Process;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: ProcessRunner::class, lifecycle: Lifecycle::SINGLETON)]
interface ProcessRunnerInterface
{
    /**
     * @throws ProcessException
     */
    public function run(
        ProcessCommandInterface $command,
    ): ProcessResultInterface;

    /**
     * @throws ProcessException
     */
    public function start(
        ProcessCommandInterface $command,
    ): ProcessHandleInterface;
}
