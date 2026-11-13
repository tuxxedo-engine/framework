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

namespace Tuxxedo\Http\Request\Context;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorException;

interface BodyContextInterface
{
    /**
     * @throws HttpException
     */
    public function getStream(): StreamInterface;

    public function getRaw(): string;

    public function size(): ?int;

    public function isJson(): bool;

    public function isXml(): bool;

    public function isCsv(): bool;

    public function isForm(): bool;

    public function isText(): bool;

    /**
     * @throws \JsonException
     */
    public function getJson(
        bool $associative = false,
        int $flags = 0,
    ): mixed;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param list<string>|null $only
     * @return TClassName
     *
     * @throws HttpException
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function jsonMap(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        int $flags = 0,
    ): object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param list<string>|null $only
     * @return list<TClassName>
     *
     * @throws HttpException
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function jsonMapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        int $flags = 0,
    ): array;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param list<string>|null $only
     * @return TClassName
     *
     * @throws HttpException
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function xmlMap(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
    ): object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param list<string>|null $only
     * @return list<TClassName>
     *
     * @throws HttpException
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function xmlMapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
    ): array;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @return list<TClassName>
     *
     * @throws HttpException
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function csvMapList(
        string $className,
        bool $skipUnknownKeys = true,
        string $separator = ',',
        string $enclosure = '"',
    ): array;
}
