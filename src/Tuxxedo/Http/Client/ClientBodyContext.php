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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Serializer\SerializerManagerInterface;

class ClientBodyContext implements BodyContextInterface
{
    public function __construct(
        private readonly ?SerializerManagerInterface $serializerManager,
        private StreamInterface|string $body = '',
        private readonly ?string $contentType = null,
    ) {
    }

    public function getStream(): StreamInterface
    {
        if ($this->body instanceof StreamInterface) {
            return $this->body;
        }

        $stream = ResourceStream::fromTemporary();

        $stream->write($this->body);
        $stream->rewind();

        return $stream;
    }

    public function getRaw(): string
    {
        if ($this->body instanceof StreamInterface) {
            $this->body = $this->body->getContents();
        }

        return $this->body;
    }

    public function size(): ?int
    {
        if ($this->body instanceof StreamInterface) {
            return $this->body->getSize();
        }

        return \strlen($this->body);
    }

    public function isJson(): bool
    {
        $mediaType = $this->mediaType();

        if ($mediaType === null) {
            return false;
        }

        return $mediaType === 'application/json' || \str_ends_with($mediaType, '+json');
    }

    public function isXml(): bool
    {
        $mediaType = $this->mediaType();

        if ($mediaType === null) {
            return false;
        }

        return $mediaType === 'application/xml' ||
            $mediaType === 'text/xml' ||
            \str_ends_with($mediaType, '+xml');
    }

    public function isCsv(): bool
    {
        $mediaType = $this->mediaType();

        if ($mediaType === null) {
            return false;
        }

        return $mediaType === 'text/csv' ||
            $mediaType === 'application/csv';
    }

    public function isForm(): bool
    {
        $mediaType = $this->mediaType();

        if ($mediaType === null) {
            return false;
        }

        return $mediaType === 'application/x-www-form-urlencoded' ||
            $mediaType === 'multipart/form-data';
    }

    public function isText(): bool
    {
        $mediaType = $this->mediaType();

        if ($mediaType === null) {
            return false;
        }

        return \str_starts_with($mediaType, 'text/');
    }

    public function getJson(
        bool $associative = false,
        int $flags = 0,
    ): mixed {
        return \json_decode(
            json: $this->getRaw(),
            associative: $associative,
            flags: $flags | \JSON_THROW_ON_ERROR,
        );
    }

    public function jsonMap(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        int $flags = 0,
    ): object {
        return $this->serializerManager()->json(
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            flags: $flags,
        )->decode(
            json: $this->getRaw(),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    public function jsonMapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        int $flags = 0,
    ): array {
        return $this->serializerManager()->json(
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            flags: $flags,
        )->decodeList(
            json: $this->getRaw(),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    public function xmlMap(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
    ): object {
        return $this->serializerManager()->xml(
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
        )->decode(
            xml: $this->getRaw(),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    public function xmlMapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
    ): array {
        return $this->serializerManager()->xml(
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
        )->decodeList(
            xml: $this->getRaw(),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    public function csvMapList(
        string $className,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        string $separator = ',',
        string $enclosure = '"',
    ): array {
        return $this->serializerManager()->csv(
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            separator: $separator,
            enclosure: $enclosure,
        )->decode(
            csv: $this->getRaw(),
            className: $className,
        );
    }

    /**
     * @throws HttpException
     */
    private function serializerManager(): SerializerManagerInterface
    {
        if ($this->serializerManager === null) {
            throw HttpException::fromInternalServerError();
        }

        return $this->serializerManager;
    }

    private function mediaType(): ?string
    {
        if ($this->contentType === null) {
            return null;
        }

        $contentType = $this->contentType;
        $semicolon = \strpos($contentType, ';');

        if ($semicolon !== false) {
            $contentType = \substr($contentType, 0, $semicolon);
        }

        $contentType = \strtolower(\trim($contentType));

        if ($contentType === '') {
            return null;
        }

        return $contentType;
    }
}
