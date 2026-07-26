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

class FileFactory
{
    private static ?\finfo $finfo = null;

    #[\NoDiscard]
    public static function fromBytes(
        string $bytes,
        ?string $name = null,
        ?string $mimeType = null,
    ): File {
        return new File(
            name: $name,
            mimeType: $mimeType,
            bytes: $bytes,
        );
    }

    /**
     * @throws FileException
     */
    #[\NoDiscard]
    public static function fromPath(
        string $path,
        ?string $name = null,
        ?string $mimeType = null,
    ): LocalFile {
        return new LocalFile(
            path: $path,
            name: $name ?? \basename($path),
            mimeType: $mimeType ?? self::detectMimeType(
                path: $path,
            ),
        );
    }

    private static function detectMimeType(
        string $path,
    ): ?string {
        if (self::$finfo === null) {
            if (!\extension_loaded('fileinfo')) {
                return null; // @codeCoverageIgnore
            }

            self::$finfo = new \finfo(\FILEINFO_MIME_TYPE);
        }

        $result = @\finfo_file(self::$finfo, $path);

        return $result === false
            ? null
            : $result;
    }
}
