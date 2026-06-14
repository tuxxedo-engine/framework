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

namespace Support\Http\Request\Context;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\UploadedFilesContextInterface;
use Tuxxedo\Http\UploadedFileInterface;

class StubUploadedFilesContext implements UploadedFilesContextInterface
{
    public function has(
        string $path,
    ): bool {
        return false;
    }

    public function file(
        string $path,
    ): UploadedFileInterface {
        throw HttpException::fromInternalServerError();
    }

    public function arrayOfFile(
        string $path,
    ): array {
        throw HttpException::fromInternalServerError();
    }
}
