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

class ProcessException extends \Exception
{
    public static function fromLaunchFailure(
        string $binary,
    ): self {
        return new self(
            message: \sprintf(
                'Failed to launch process: %s',
                $binary,
            ),
        );
    }

    public static function fromTimeout(
        int $seconds,
    ): self {
        return new self(
            message: \sprintf(
                'Process exceeded timeout of %d seconds',
                $seconds,
            ),
        );
    }

    public static function fromOutputLimitExceeded(
        int $bytes,
    ): self {
        return new self(
            message: \sprintf(
                'Process output exceeded limit of %d bytes',
                $bytes,
            ),
        );
    }

    public static function fromWriteFailure(): self
    {
        return new self(
            message: 'Failed to write to process stdin',
        );
    }
}
