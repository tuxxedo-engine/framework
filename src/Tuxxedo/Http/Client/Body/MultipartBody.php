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
use Tuxxedo\Http\Stream\Stream;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Serializer\SerializerManagerInterface;

readonly class MultipartBody implements RequestBodyInterface
{
    public string $boundary;
    public string $contentType;

    /**
     * @param array<string, string> $fields
     * @param array<string, FileInterface|list<FileInterface>> $files
     */
    public function __construct(
        public array $fields = [],
        public array $files = [],
    ) {
        $this->boundary = 'TuxxedoBoundary' . \bin2hex(\random_bytes(16));
        $this->contentType = 'multipart/form-data; boundary=' . $this->boundary;
    }

    public function encode(
        SerializerManagerInterface $serializerManager,
    ): StreamInterface {
        $parts = $this->parts();
        $size = \strlen($this->closingDelimiter());

        foreach ($parts as [$head, $body]) {
            $bodySize = $body instanceof FileInterface
                ? $body->size
                : \strlen($body);

            if ($bodySize === null) {
                $size = null;

                break;
            }

            $size += \strlen($head) + $bodySize + 2;
        }

        return Stream::fromGenerator(
            generator: function () use ($parts): \Generator {
                foreach ($parts as [$head, $body]) {
                    yield $head;

                    if ($body instanceof PathFileInterface) {
                        $stream = ResourceStream::fromFile($body->path);

                        while (($chunk = $stream->read()) !== null) {
                            yield $chunk;
                        }

                        $stream->close();
                    } elseif ($body instanceof FileInterface) {
                        yield $body->contents();
                    } else {
                        yield $body;
                    }

                    yield "\r\n";
                }

                yield $this->closingDelimiter();
            },
            autoFlush: false,
            size: $size,
        );
    }

    /**
     * @return list<array{0: string, 1: FileInterface|string}>
     */
    private function parts(): array
    {
        $parts = [];

        foreach ($this->fields as $name => $value) {
            $parts[] = [
                $this->head(
                    disposition: 'form-data; name="' . self::escape($name) . '"',
                ),
                $value,
            ];
        }

        foreach ($this->files as $name => $files) {
            $files = \is_array($files)
                ? $files
                : [
                    $files,
                ];

            foreach ($files as $file) {
                $parts[] = [
                    $this->head(
                        disposition: 'form-data; name="' . self::escape($name) . '"; filename="' . self::escape(self::filename($file, $name)) . '"',
                        contentType: $file->mimeType ?? 'application/octet-stream',
                    ),
                    $file,
                ];
            }
        }

        return $parts;
    }

    private function head(
        string $disposition,
        ?string $contentType = null,
    ): string {
        $head = '--' . $this->boundary . "\r\n" .
            'Content-Disposition: ' . $disposition . "\r\n";

        if ($contentType !== null) {
            $contentType = \str_replace(
                [
                    "\r",
                    "\n",
                ],
                '',
                $contentType,
            );

            $head .= 'Content-Type: ' . $contentType . "\r\n";
        }

        return $head . "\r\n";
    }

    private function closingDelimiter(): string
    {
        return '--' . $this->boundary . "--\r\n";
    }

    private static function filename(
        FileInterface $file,
        string $name,
    ): string {
        if ($file->name !== null) {
            return $file->name;
        }

        if ($file instanceof PathFileInterface) {
            return \basename($file->path);
        }

        return $name;
    }

    private static function escape(
        string $value,
    ): string {
        return \str_replace(
            [
                '"',
                "\r",
                "\n",
            ],
            [
                '%22',
                '%0D',
                '%0A',
            ],
            $value,
        );
    }
}
