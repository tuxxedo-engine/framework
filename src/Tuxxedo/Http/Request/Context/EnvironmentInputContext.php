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
use Tuxxedo\Http\InputContext;
use Tuxxedo\Reflection\EnumHydrator;
use Tuxxedo\Serializer\SerializerManagerInterface;

class EnvironmentInputContext implements InputContextInterface
{
    public function __construct(
        private readonly InputContext $inputContext,
        private readonly SerializerManagerInterface $serializerManager,
    ) {
    }

    /**
     * @return mixed[]
     */
    private function input(): array
    {
        return match ($this->inputContext) {
            InputContext::GET => $_GET,
            InputContext::POST => $_POST,
            InputContext::COOKIE => $_COOKIE,
        };
    }

    public function keys(): array
    {
        $keys = [];

        foreach (\array_keys($this->input()) as $key) {
            $keys[] = (string) $key;
        }

        return $keys;
    }

    public function has(
        string $name,
    ): bool {
        return \array_key_exists($name, $this->input());
    }

    public function raw(
        string $name,
        mixed $default = null,
    ): mixed {
        $data = $this->input();

        if (!\array_key_exists($name, $data)) {
            return $default;
        }

        $input = \filter_var($data[$name], \FILTER_UNSAFE_RAW);

        return $input !== false && $default !== false
            ? $input
            : $default;
    }

    public function rawArray(
        string $name,
        array $default = [],
    ): array {
        $input = $this->input();

        if (!\array_key_exists($name, $input)) {
            return $default;
        }

        $value = \filter_var($input[$name], \FILTER_UNSAFE_RAW, \FILTER_REQUIRE_ARRAY);

        return $value !== false
            ? $value
            : $default;
    }

    public function int(
        string $name,
        int $default = 0,
    ): int {
        $input = $this->input();

        if (!\array_key_exists($name, $input)) {
            return $default;
        }

        $value = \filter_var($input[$name], \FILTER_VALIDATE_INT);

        if (!\is_int($value)) {
            return $default;
        }

        return $value;
    }

    public function bool(
        string $name,
        bool $default = false,
    ): bool {
        $input = $this->input();

        if (!\array_key_exists($name, $input)) {
            return $default;
        }

        $value = \filter_var($input[$name], \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);

        if (!\is_bool($value)) {
            return $default;
        }

        return $value;
    }

    public function float(
        string $name,
        float $default = 0.0,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
    ): float {
        $input = $this->input();

        if (!\array_key_exists($name, $input)) {
            return $default;
        }

        $raw = $input[$name];

        if (\is_string($raw)) {
            $raw = \str_replace($thousandSeparator, '', $raw);
        }

        $value = \filter_var(
            $raw,
            \FILTER_VALIDATE_FLOAT,
            [
                'options' => [
                    'decimal' => $decimalPoint,
                ],
            ],
        );

        if (!\is_float($value)) {
            return $default;
        }

        return $value;
    }

    public function string(
        string $name,
        string $default = '',
    ): string {
        $input = $this->input();

        if (!\array_key_exists($name, $input)) {
            return $default;
        }

        $value = \filter_var($input[$name]);

        if (!\is_string($value)) {
            return $default;
        }

        return $value;
    }

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
    ): object {
        $input = $this->input();

        if (
            !\enum_exists($enum) ||
            !\array_key_exists($name, $input)
        ) {
            throw HttpException::fromInternalServerError();
        }

        $value = \filter_var($input[$name]);

        if (!\is_string($value)) {
            throw HttpException::fromInternalServerError();
        }

        $case = EnumHydrator::hydrateCaseInsensitive(
            enumClass: $enum,
            value: $value,
        );

        if ($case === null) {
            throw HttpException::fromInternalServerError();
        }

        return $case;
    }

    public function arrayOfInt(
        string $name,
    ): array {
        $input = $this->input();

        if (!\array_key_exists($name, $input)) {
            return [];
        }

        $value = \filter_var($input[$name], \FILTER_VALIDATE_INT, \FILTER_REQUIRE_ARRAY);

        if (!\is_array($value)) {
            return [];
        }

        return \array_filter($value, \is_int(...));
    }

    public function arrayOfBool(
        string $name,
    ): array {
        $input = $this->input();

        if (!\array_key_exists($name, $input)) {
            return [];
        }

        $value = \filter_var($input[$name], \FILTER_VALIDATE_BOOL, \FILTER_REQUIRE_ARRAY | \FILTER_NULL_ON_FAILURE);

        if (!\is_array($value)) {
            return [];
        }

        return \array_filter($value, \is_bool(...));
    }

    public function arrayOfFloat(
        string $name,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
    ): array {
        $input = $this->input();

        if (!\array_key_exists($name, $input)) {
            return [];
        }

        $raw = $input[$name];

        if (\is_array($raw)) {
            $raw = \array_map(
                static fn (mixed $entry): mixed => \is_string($entry)
                    ? \str_replace($thousandSeparator, '', $entry)
                    : $entry,
                $raw,
            );
        }

        $value = \filter_var(
            $raw,
            \FILTER_VALIDATE_FLOAT,
            [
                'flags' => \FILTER_REQUIRE_ARRAY,
                'options' => [
                    'decimal' => $decimalPoint,
                ],
            ],
        );

        if (!\is_array($value)) {
            return [];
        }

        return \array_filter($value, \is_float(...));
    }

    public function arrayOfString(
        string $name,
    ): array {
        $input = $this->input();

        if (!\array_key_exists($name, $input)) {
            return [];
        }

        $value = \filter_var($input[$name], \FILTER_DEFAULT, \FILTER_REQUIRE_ARRAY);

        if (!\is_array($value)) {
            return [];
        }

        return \array_filter($value, \is_string(...));
    }

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
    ): array {
        $input = $this->input();

        if (
            !\enum_exists($enum) ||
            !\array_key_exists($name, $input)
        ) {
            throw HttpException::fromInternalServerError();
        }

        $values = \filter_var($input[$name], \FILTER_DEFAULT, \FILTER_REQUIRE_ARRAY);

        if (!\is_array($values)) {
            throw HttpException::fromInternalServerError();
        }

        $enums = [];

        foreach (\array_filter($values, \is_string(...)) as $value) {
            $case = EnumHydrator::hydrateCaseInsensitive(
                enumClass: $enum,
                value: $value,
            );

            if ($case === null) {
                throw HttpException::fromInternalServerError();
            }

            $enums[] = $case;
        }

        return $enums;
    }

    public function map(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
    ): object {
        $this->guardMappable();

        return $this->serializerManager->array($skipUnknownKeys)->fromArray(
            data: $this->input(),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    public function mapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
    ): array {
        $this->guardMappable();

        return $this->serializerManager->array($skipUnknownKeys)->fromArrayList(
            data: $this->input(),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    public function jsonMap(
        string $name,
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        int $flags = 0,
    ): object {
        $this->guardMappable();

        return $this->serializerManager->json(
            skipUnknownKeys: $skipUnknownKeys,
            flags: $flags,
        )->decode(
            json: $this->string($name),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    public function jsonMapList(
        string $name,
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        int $flags = 0,
    ): array {
        $this->guardMappable();

        return $this->serializerManager->json(
            skipUnknownKeys: $skipUnknownKeys,
            flags: $flags,
        )->decodeList(
            json: $this->string($name),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    /**
     * @throws HttpException
     */
    private function guardMappable(): void
    {
        if ($this->inputContext === InputContext::COOKIE) {
            throw HttpException::fromInternalServerError();
        }
    }
}
