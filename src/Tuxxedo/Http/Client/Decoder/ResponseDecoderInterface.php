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

namespace Tuxxedo\Http\Client\Decoder;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorException;

interface ResponseDecoderInterface
{
    public function supports(
        BodyContextInterface $body,
    ): bool;

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
    public function map(
        BodyContextInterface $body,
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
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
    public function mapList(
        BodyContextInterface $body,
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
    ): array;
}
