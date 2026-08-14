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

class ProcessCommand implements ProcessCommandInterface
{
    /**
     * @param list<string> $arguments
     * @param array<string, string>|null $environment
     */
    public function __construct(
        public readonly string $binary,
        public readonly array $arguments = [],
        public readonly ?string $stdin = null,
        public readonly ?string $workingDirectory = null,
        public readonly ?array $environment = null,
        public readonly ?int $timeoutSeconds = 30,
        public readonly ?int $maxOutputBytes = null,
    ) {
    }
}
