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

namespace Tuxxedo\Http;

use Tuxxedo\File\FileException;

class UploadedFile implements UploadedFileInterface
{
    private static ?\finfo $finfo = null;
    private ?string $resolvedType = null;

    public string $mimeType {
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
        if ($this->resolvedType !== null) {
            return $this->resolvedType;
        }

        if (self::$finfo !== null) {
            $type = @\finfo_file(self::$finfo, $this->temporaryPath);

            if ($type !== false) {
                $this->isTrustedType = true;

                return $this->resolvedType = $type;
            }
        }

        return $unsafeType;
    }

    public function isTrustedType(): bool
    {
        if ($this->resolvedType === null) {
            $this->resolveType($this->browserType);
        }

        return $this->isTrustedType;
    }

    #[\NoDiscard]
    public function contents(): string
    {
        $contents = @\file_get_contents($this->temporaryPath);

        if ($contents === false) {
            throw FileException::fromReadFailure(
                path: $this->temporaryPath,
            );
        }

        return $contents;
    }

    public function moveTo(
        string $fileName,
    ): bool {
        return @\move_uploaded_file($this->temporaryPath, $fileName);
    }
}
