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

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\UploadedFileInterface;

interface UploadedFilesContextInterface
{
    public function has(
        string $path,
    ): bool;

    /**
     * @throws HttpException
     * @throws UploadedFileException
     */
    public function get(
        string $path,
    ): UploadedFileInterface;
}
