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

namespace Tuxxedo\File;

class FileException extends \Exception
{
    public static function fromReadFailure(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Failed to read file at "%s"',
                $path,
            ),
        );
    }

    public static function fromNotAFile(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Path "%s" does not refer to a regular file',
                $path,
            ),
        );
    }

    public static function fromNotADirectory(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Path "%s" does not refer to a directory',
                $path,
            ),
        );
    }
}
