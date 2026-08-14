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

interface ProcessHandleInterface
{
    public int $pid {
        get;
    }

    public bool $isRunning {
        get;
    }

    /**
     * @throws ProcessException
     */
    public function poll(): ?ProcessResultInterface;

    /**
     * @throws ProcessException
     */
    public function wait(): ProcessResultInterface;

    /**
     * @throws ProcessException
     */
    public function terminate(): void;
}
