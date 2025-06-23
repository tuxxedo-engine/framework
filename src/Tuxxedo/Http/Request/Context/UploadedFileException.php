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
}
