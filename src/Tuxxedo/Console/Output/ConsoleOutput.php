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

namespace Tuxxedo\Console\Output;

use Tuxxedo\Console\Stream\PhpOutputStream;

class ConsoleOutput implements ConsoleOutputInterface
{
    public function __construct(
        public readonly OutputInterface $stdout,
        public readonly OutputInterface $stderr,
    ) {
    }

    public static function createFromStandardStreams(): self
    {
        return new self(
            stdout: new StreamOutput(
                stream: new PhpOutputStream(
                    resource: \STDOUT,
                ),
            ),
            stderr: new StreamOutput(
                stream: new PhpOutputStream(
                    resource: \STDERR,
                ),
            ),
        );
    }
}
