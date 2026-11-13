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

use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\WeightedHeader;
use Tuxxedo\Http\WeightedHeaderInterface;
use Tuxxedo\Reflection\EnumHydrator;

class ClientHeaderContext implements HeaderContextInterface
{
    /**
     * @var list<HeaderInterface>
     */
    private readonly array $headers;

    /**
     * @var array<string, array{name: string, values: list<string>}>
     */
    private readonly array $index;

    /**
     * @param HeaderInterface[] $headers
     */
    public function __construct(
        array $headers = [],
    ) {
        $index = [];

        foreach ($headers as $header) {
            $key = \strtolower($header->name);

            if (!\array_key_exists($key, $index)) {
                $index[$key] = [
                    'name' => $header->name,
                    'values' => [],
                ];
            }

            $index[$key]['values'][] = $header->value;
        }

        $this->headers = \array_values($headers);
        $this->index = $index;
    }

    public function all(): array
    {
        return $this->headers;
    }

    public function has(
        string $name,
    ): bool {
        return \array_key_exists(\strtolower($name), $this->index);
    }

    public function get(
        string $name,
    ): HeaderInterface {
        $value = $this->value($name);
        $name = $this->index[\strtolower($name)]['name'];

        if ($this->isWeightedValue($value)) {
            return new WeightedHeader(
                name: $name,
                value: $value,
            );
        }

        return new Header(
            name: $name,
            value: $value,
        );
    }

    public function isWeighted(
        string $name,
    ): bool {
        if (!$this->has($name)) {
            return false;
        }

        return $this->isWeightedValue($this->value($name));
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
        $header = $this->get($name);

        if (!$header instanceof WeightedHeaderInterface) {
            throw HttpException::fromInternalServerError();
        }

        return $header;
    }

    public function int(
        string $name,
    ): int {
        return (int) $this->value($name);
    }

    public function bool(
        string $name,
    ): bool {
        return (bool) $this->value($name);
    }

    public function float(
        string $name,
    ): float {
        return (float) $this->value($name);
    }

    public function string(
        string $name,
    ): string {
        return $this->value($name);
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
        if (!\enum_exists($enum)) {
            throw HttpException::fromInternalServerError(); // @codeCoverageIgnore
        }

        $case = EnumHydrator::hydrateCaseInsensitive(
            enumClass: $enum,
            value: $this->value($name),
        );

        if ($case === null) {
            throw HttpException::fromInternalServerError();
        }

        return $case;
    }

    /**
     * @throws HttpException
     */
    private function value(
        string $name,
    ): string {
        $key = \strtolower($name);

        if (!\array_key_exists($key, $this->index)) {
            throw HttpException::fromInternalServerError();
        }

        return \join(', ', $this->index[$key]['values']);
    }
}
