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

namespace Tuxxedo\Http\Request\Context;

class UploadedFileException extends \Exception
{
    public function __construct(
        string $message,
    ) {
        parent::__construct(
            message: $message,
        );
    }

    public static function fromSizeMismatch(): self
    {
        return new self(
            message: 'Uploaded file exceeds size limit',
        );
    }

    public static function fromIncompleteFile(): self
    {
        return new self(
            message: 'Uploaded file was not fully uploaded',
        );
    }

    public static function fromSaveFile(): self
    {
        return new self(
            message: 'Uploaded file could not be saved on server',
        );
    }

    public static function fromExtensionError(): self
    {
        return new self(
            message: 'Uploaded file could not be uploaded because an extension prevented it',
        );
    }

    public static function fromSingleFileExpectedAtPath(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Expected a single uploaded file at path "%s", but the path resolves to an array of files or a nested structure',
                $path,
            ),
        );
    }

    public static function fromArrayExpectedAtPath(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Expected an array of uploaded files at path "%s", but the path resolves to a single file',
                $path,
            ),
        );
    }

    public static function fromListExpectedAtPath(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Expected a list-keyed (numerically indexed) array of uploaded files at path "%s", but the path resolves to an associative-keyed array',
                $path,
            ),
        );
    }

    public static function fromLeavesExpectedAtPath(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Expected an array of file records at path "%s", but entries are nested structures rather than file records — the path is too shallow',
                $path,
            ),
        );
    }
}
