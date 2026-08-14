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

interface ProcessResultInterface
{
    public int $exitCode {
        get;
    }

    public string $stdout {
        get;
    }

    public string $stderr {
        get;
    }

    public bool $isSuccess {
        get;
    }
}
