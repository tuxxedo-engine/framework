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

namespace Support\Http\Request\Context;

use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\WeightedHeader;
use Tuxxedo\Http\WeightedHeaderInterface;

class StubHeaderContext implements HeaderContextInterface
{
    /**
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        array $headers = [],
    ) {
        $this->headers = $headers;
    }

    public function all(): array
    {
        $result = [];

        foreach ($this->headers as $name => $value) {
            $result[] = new Header($name, $value);
        }

        return $result;
    }

    public function has(
        string $name,
    ): bool {
        return \array_key_exists($name, $this->headers);
    }

    public function get(
        string $name,
    ): HeaderInterface {
        if (!\array_key_exists($name, $this->headers)) {
            throw HttpException::fromInternalServerError();
        }

        return new Header($name, $this->headers[$name]);
    }

    public function isWeighted(
        string $name,
    ): bool {
        return false;
    }

    public function isWeightedValue(
        HeaderInterface|WeightedHeaderInterface|string $value,
    ): bool {
        return false;
    }

    public function getWeighted(
        string $name,
    ): WeightedHeaderInterface {
        if (!\array_key_exists($name, $this->headers)) {
            throw HttpException::fromInternalServerError();
        }

        return new WeightedHeader($name, $this->headers[$name]);
    }

    public function getInt(
        string $name,
    ): int {
        return 0;
    }

    public function getBool(
        string $name,
    ): bool {
        return false;
    }

    public function getFloat(
        string $name,
    ): float {
        return 0.0;
    }

    public function getString(
        string $name,
    ): string {
        return $this->headers[$name] ?? '';
    }

    public function getEnum(
        string $name,
        string $enum,
    ): object {
        throw HttpException::fromInternalServerError();
    }
}
