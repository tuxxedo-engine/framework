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

namespace Tuxxedo\File\Storage;

class StorageException extends \Exception
{
    public static function fromInvalidKey(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Storage key "%s" is invalid',
                $key,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromKeyEscapesRoot(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Storage key "%s" resolves outside the configured root directory',
                $key,
            ),
        );
    }

    public static function fromKeyNotFound(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Storage key "%s" does not exist',
                $key,
            ),
        );
    }

    public static function fromWriteFailure(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Failed to write storage key "%s"',
                $key,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromDeleteFailure(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Failed to delete storage key "%s"',
                $key,
            ),
        );
    }

    public static function fromMissingRoot(
        string $root,
    ): self {
        return new self(
            message: \sprintf(
                'Storage root directory "%s" does not exist',
                $root,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromCaseInsensitiveFilesystem(
        string $root,
    ): self {
        return new self(
            message: \sprintf(
                'Storage root "%s" is on a case-insensitive filesystem; storage keys are case-sensitive by contract. Either use a case-sensitive filesystem, or set LocalStorageConfig::$allowCaseInsensitiveFilesystem to true to acknowledge the limitation',
                $root,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromCaseMismatch(
        string $key,
        string $actualCase,
    ): self {
        return new self(
            message: \sprintf(
                'Storage key "%s" resolves to a file with different case "%s" on the underlying filesystem',
                $key,
                $actualCase,
            ),
        );
    }
}
