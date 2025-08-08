<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Http\Request\Context;

use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\WeightedHeader;
use Tuxxedo\Http\WeightedHeaderInterface;
use UnitEnum as T;

class EnvironmentHeaderContext implements HeaderContextInterface
{
    /**
     * @var array<string, string>
     */
    private array $headers = [];

    protected bool $lazyLoaded = false;

    protected function lazyLoad(): void
    {
        if ($this->lazyLoaded) {
            return;
        }

        foreach ($_SERVER as $name => $value) {
            if (
                !\str_starts_with($name, 'HTTP_') ||
                \str_starts_with($name, 'HTTP_COOKIE') ||
                !\is_scalar($value)
            ) {
                continue;
            }

            $this->headers[\str_replace(' ', '-', \ucwords(\str_replace('_', ' ', \strtolower(\substr($name, 5)))))] = (string) $value;
        }

        $this->lazyLoaded = true;
    }

    public function all(): array
    {
        $this->lazyLoad();

        $headers = [];

        foreach ($this->headers as $name => $value) {
            $headers[] = match (true) {
                $this->isWeightedValue($value) => new WeightedHeader(
                    name: $name,
                    value: $value,
                ),
                default => new Header(
                    name: $name,
                    value: $value,
                ),
            };
        }

        return $headers;
    }

    public function has(string $name): bool
    {
        $this->lazyLoad();

        return \array_key_exists($name, $this->headers);
    }

    public function get(
        string $name,
    ): HeaderInterface {
        $this->lazyLoad();

        if (!\array_key_exists($name, $this->headers)) {
            throw HttpException::fromInternalServerError();
        }

        if ($this->isWeightedValue($this->headers[$name])) {
            return new WeightedHeader(
                name: $name,
                value: $this->headers[$name],
            );
        }

        return new Header(
            name: $name,
            value: $this->headers[$name],
        );
    }

    public function isWeighted(
        string $name,
    ): bool {
        $this->lazyLoad();

        if (!\array_key_exists($name, $this->headers)) {
            return false;
        }

        return $this->isWeightedValue($this->headers[$name]);
    }

    public function isWeightedValue(
        HeaderInterface|WeightedHeaderInterface|string $value,
    ): bool {
        if (!\is_string($value)) {
            $value = $value->value;
        }

        return \preg_match('/;\s*[qv]=("?)[0-9.]+\1/', $value) === 1;
    }

    public function getWeighted(
        string $name,
    ): WeightedHeaderInterface {
        $this->lazyLoad();

        if (
            !\array_key_exists($name, $this->headers) ||
            !$this->isWeightedValue($this->headers[$name])
        ) {
            throw HttpException::fromInternalServerError();
        }

        return new WeightedHeader(
            name: $name,
            value: $this->headers[$name],
        );
    }

    public function getInt(string $name): int
    {
        if (!\array_key_exists($name, $this->headers)) {
            throw HttpException::fromInternalServerError();
        }

        return (int) $this->headers[$name];
    }

    public function getBool(string $name): bool
    {
        $this->lazyLoad();

        if (!\array_key_exists($name, $this->headers)) {
            throw HttpException::fromInternalServerError();
        }

        return (bool) $this->headers[$name];
    }

    public function getFloat(string $name): float
    {
        $this->lazyLoad();

        if (!\array_key_exists($name, $this->headers)) {
            throw HttpException::fromInternalServerError();
        }

        return (float) $this->headers[$name];
    }

    public function getString(string $name): string
    {
        $this->lazyLoad();

        if (!\array_key_exists($name, $this->headers)) {
            throw HttpException::fromInternalServerError();
        }

        return $this->headers[$name];
    }

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return TEnum&\UnitEnum
     *
     * @throws HttpException
     */
    public function getEnum(string $name, string $enum): object
    {
        if (!\enum_exists($enum)) {
            throw HttpException::fromInternalServerError();
        }

        $this->lazyLoad();

        if (!\array_key_exists($name, $this->headers)) {
            throw HttpException::fromInternalServerError();
        }

        foreach ($enum::cases() as $case) {
            if (\strcasecmp($case->name, $this->headers[$name]) === 0) {
                return $case;
            }
        }

        throw HttpException::fromInternalServerError();
    }
}
