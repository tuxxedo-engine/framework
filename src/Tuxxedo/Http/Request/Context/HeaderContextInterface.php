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

use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\WeightedHeaderInterface;

interface HeaderContextInterface
{
    public ?string $preferredLanguage {
        get;
    }

    /**
     * @var string[]
     */
    public array $preferredLanguages {
        get;
    }

    public string $userAgent {
        get;
    }

    /**
     * @return HeaderInterface[]
     */
    public function all(): array;

    public function has(
        string $name,
    ): bool;

    /**
     * @throws HttpException
     */
    public function get(
        string $name,
    ): HeaderInterface;

    public function isWeighted(
        string $name,
    ): bool;

    public function isWeightedValue(
        HeaderInterface|WeightedHeaderInterface|string $value,
    ): bool;

    /**
     * @throws HttpException
     */
    public function getWeighted(
        string $name,
    ): WeightedHeaderInterface;

    /**
     * @throws HttpException
     */
    public function int(
        string $name,
    ): int;

    /**
     * @throws HttpException
     */
    public function bool(
        string $name,
    ): bool;

    /**
     * @throws HttpException
     */
    public function float(
        string $name,
    ): float;

    /**
     * @throws HttpException
     */
    public function string(
        string $name,
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
}
