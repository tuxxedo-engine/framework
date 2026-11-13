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

namespace Tuxxedo\Http\Client\Body;

use Tuxxedo\File\FileInterface;
use Tuxxedo\File\PathFileInterface;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Serializer\SerializerManagerInterface;

readonly class FileBody implements RequestBodyInterface
{
    public string $contentType;

    public function __construct(
        public FileInterface $file,
        ?string $contentType = null,
    ) {
        $this->contentType = $contentType ?? $file->mimeType ?? 'application/octet-stream';
    }

    public function encode(
        SerializerManagerInterface $serializerManager,
    ): StreamInterface|string {
        if ($this->file instanceof PathFileInterface) {
            return ResourceStream::fromFile($this->file->path);
        }

        return $this->file->contents();
    }
}
