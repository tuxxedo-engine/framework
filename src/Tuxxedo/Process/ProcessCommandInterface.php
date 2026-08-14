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

interface ProcessCommandInterface
{
    public string $binary {
        get;
    }

    /**
     * @var list<string>
     */
    public array $arguments {
        get;
    }

    public ?string $stdin {
        get;
    }

    public ?string $workingDirectory {
        get;
    }

    /**
     * @var array<string, string>|null
     */
    public ?array $environment {
        get;
    }

    public ?int $timeoutSeconds {
        get;
    }

    public ?int $maxOutputBytes {
        get;
    }
}
