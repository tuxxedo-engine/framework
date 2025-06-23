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

namespace Tuxxedo\Http;

class UploadedFile implements UploadedFileInterface
{
    private static ?\finfo $finfo = null;

    public string $type {
        get {
            return $this->resolveType($this->browserType);
        }
    }

    private bool $isTrustedType = false;

    public function __construct(
        public readonly string $name,
        private readonly string $browserType,
        public readonly int $size,
        public readonly string $temporaryPath,
        public readonly string $browserPath,
    ) {
        if (self::$finfo === null && \extension_loaded('fileinfo')) {
            self::$finfo = new \finfo(\FILEINFO_MIME_TYPE);
        }
    }

    private function resolveType(
        string $unsafeType,
    ): string {
        static $resolvedType;

        if (\is_string($resolvedType)) {
            return $resolvedType;
        }

        if (self::$finfo !== null) {
            $type = \finfo_file(self::$finfo, $this->temporaryPath);

            if ($type !== false) {
                $this->isTrustedType = true;

                return $resolvedType = $type;
            }
        }

        return $unsafeType;
    }

    public function isTrustedType(): bool
    {
        return $this->isTrustedType;
    }

    public function getContents(): ?string
    {
        $contents = @\file_get_contents($this->temporaryPath);

        if ($contents !== false) {
            return $contents;
        }

        return null;
    }

    public function moveTo(
        string $fileName,
    ): bool {
        return @\move_uploaded_file($this->temporaryPath, $fileName);
    }
}
