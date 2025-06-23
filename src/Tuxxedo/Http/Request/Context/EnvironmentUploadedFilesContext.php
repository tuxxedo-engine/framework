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
use Tuxxedo\Http\UploadedFile;
use Tuxxedo\Http\UploadedFileInterface;

/**
 * @phpstan-type phpUploadedFileArray array{name: string, type: string, size: int, error: int, tmp_name: string, full_path: string}
 */
class EnvironmentUploadedFilesContext implements UploadedFilesContextInterface
{
    /**
     * @param string $path
     * @return phpUploadedFileArray|null
     */
    private function path(string $path): ?array
    {
        $index = $_FILES;

        foreach (\explode('.', $path) as $part) {
            if (!\is_array($index)) {
                continue;
            } elseif (!\array_key_exists($part, $index)) {
                return null;
            }

            if (
                \is_array($index[$part]) &&
                \array_key_exists('error', $index[$part]) &&
                \is_int($index[$part]['error'])
            ) {
                /** @var phpUploadedFileArray */
                return $index[$part];
            }

            $index = $index[$part];
        }

        return null;
    }

    public function has(
        string $path,
    ): bool {
        return $this->path($path) !== null;
    }

    public function get(
        string $path,
    ): UploadedFileInterface {
        $file = $this->path($path);

        if ($file === null) {
            throw HttpException::fromInternalServerError();
        }

        match ($file['error']) {
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => throw UploadedFileException::fromSizeMismatch(),
            \UPLOAD_ERR_PARTIAL, \UPLOAD_ERR_NO_FILE => throw UploadedFileException::fromIncompleteFile(),
            \UPLOAD_ERR_NO_TMP_DIR, \UPLOAD_ERR_CANT_WRITE => throw UploadedFileException::fromSaveFile(),
            \UPLOAD_ERR_EXTENSION => throw UploadedFileException::fromExtensionError(),
            default => null,
        };

        return new UploadedFile(
            name: $file['name'],
            browserType: $file['type'],
            size: $file['size'],
            temporaryPath: $file['tmp_name'],
            browserPath: $file['full_path'],
        );
    }
}
