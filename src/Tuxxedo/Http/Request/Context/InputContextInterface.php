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
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorException;

interface InputContextInterface
{
    /**
     * @return list<string>
     */
    public function keys(): array;

    public function has(
        string $name,
    ): bool;

    public function raw(
        string $name,
        mixed $default = null,
    ): mixed;

    /**
     * @param mixed[] $default
     * @return mixed[]
     */
    public function rawArray(
        string $name,
        array $default = [],
    ): array;

    public function int(
        string $name,
        int $default = 0,
    ): int;

    public function bool(
        string $name,
        bool $default = false,
    ): bool;

    /**
     * @param '.'|',' $decimalPoint
     * @param '.'|','|' ' $thousandSeparator
     */
    public function float(
        string $name,
        float $default = 0.0,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
    ): float;

    public function string(
        string $name,
        string $default = '',
    ): string;

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return TEnum&\UnitEnum
     *
     * @throws HttpException
     */
    public function enum(
        string $name,
        string $enum,
    ): object;

    /**
     * @return int[]
     */
    public function arrayOfInt(
        string $name,
    ): array;

    /**
     * @return bool[]
     */
    public function arrayOfBool(
        string $name,
    ): array;

    /**
     * @param '.'|',' $decimalPoint
     * @param '.'|','|' ' $thousandSeparator
     * @return float[]
     */
    public function arrayOfFloat(
        string $name,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
    ): array;

    /**
     * @return string[]
     */
    public function arrayOfString(
        string $name,
    ): array;

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return array<TEnum&\UnitEnum>
     *
     * @throws HttpException
     */
    public function arrayOfEnum(
        string $name,
        string $enum,
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
    public function map(
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
    public function mapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
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
    public function jsonMap(
        string $name,
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
        string $name,
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        int $flags = 0,
    ): array;
}
