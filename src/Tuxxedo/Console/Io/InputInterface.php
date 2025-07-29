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

namespace Tuxxedo\Console\Io;

interface InputInterface
{
    public ReadableStreamInterface $stdin {
        get;
    }

    public WritableStreamInterface&ReadableStreamInterface $stdout {
        get;
    }

    public WritableStreamInterface&ReadableStreamInterface $stderr {
        get;
    }

    public int $exitCode {
        get;
    }

    public function withExitCode(
        int $exitCode,
    ): self;

    // @todo Support command line args
    // @todo Redesign this interface
}
