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

namespace Tuxxedo\Mail;

class Header implements HeaderInterface
{
    /**
     * @throws MailException
     */
    public function __construct(
        public readonly string $name,
        public readonly string $value,
    ) {
        if (
            $name === '' ||
            \preg_match('/^[\x21-\x39\x3B-\x7E]+$/', $name) !== 1
        ) {
            throw MailException::fromInvalidHeaderName($name);
        }

        self::assertValidValue($name, $value);
    }

    public function is(
        string $name,
    ): bool {
        return \strcasecmp($this->name, $name) === 0;
    }

    /**
     * @throws MailException
     */
    #[\NoDiscard]
    public function withValue(
        string $value,
    ): static {
        self::assertValidValue($this->name, $value);

        return clone (
            $this,
            [
                'value' => $value,
            ],
        );
    }

    /**
     * @throws MailException
     */
    private static function assertValidValue(
        string $name,
        string $value,
    ): void {
        if (\preg_match('/[\x00\r\n]/', $value) === 1) {
            throw MailException::fromInvalidHeaderValue($name);
        }
    }

    #[\NoDiscard]
    public function toRfc5322(): string
    {
        if (\preg_match('/[^\x00-\x7F]/', $this->value) === 1) {
            return \sprintf(
                '%s: =?UTF-8?B?%s?=',
                $this->name,
                \base64_encode($this->value),
            );
        }

        return $this->name . ': ' . $this->value;
    }
}
