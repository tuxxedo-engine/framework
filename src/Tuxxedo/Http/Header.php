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

namespace Tuxxedo\Http;

readonly class Header implements HeaderInterface
{
    /**
     * @throws HttpException
     */
    final public function __construct(
        public string $name,
        public string $value,
    ) {
        if (
            $name === '' ||
            \preg_match('/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/', $name) !== 1
        ) {
            throw HttpException::fromInternalServerError();
        }

        self::assertValidValue($value);
    }

    public function is(
        string $name,
    ): bool {
        return \strcasecmp($this->name, $name) === 0;
    }

    /**
     * @throws HttpException
     */
    public function withValue(
        string $value,
    ): static {
        self::assertValidValue($value);

        return clone (
            $this,
            [
                'value' => $value,
            ],
        );
    }

    /**
     * @throws HttpException
     */
    private static function assertValidValue(
        string $value,
    ): void {
        if (\preg_match('/[\x00\r\n]/', $value) === 1) {
            throw HttpException::fromInternalServerError();
        }
    }
}
