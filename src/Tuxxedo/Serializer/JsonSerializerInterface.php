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

namespace Tuxxedo\Serializer;

use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorException;

interface JsonSerializerInterface
{
    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @return TClassName
     *
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function decode(
        string $json,
        string $className,
    ): object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @return list<TClassName>
     *
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function decodeList(
        string $json,
        string $className,
    ): array;

    /**
     * @throws SerializerException
     */
    public function encode(
        object $value,
    ): string;

    /**
     * @param iterable<object> $values
     *
     * @throws SerializerException
     */
    public function encodeList(
        iterable $values,
    ): string;
}
