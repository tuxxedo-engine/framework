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

interface XmlSerializerInterface
{
    public ?string $indent {
        get;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param list<string>|null $only
     * @return TClassName
     *
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function decode(
        string $xml,
        string $className,
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param list<string>|null $only
     * @return list<TClassName>
     *
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function decodeList(
        string $xml,
        string $className,
        ?string $key = null,
        ?array $only = null,
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
