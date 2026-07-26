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

use Tuxxedo\Mail\Serializer\Render\EncodedWord;

class Address implements AddressInterface
{
    private const int EMAIL_MAX_LENGTH = 254;
    private const int LOCAL_PART_MAX_LENGTH = 64;

    public readonly string $localPart;
    public readonly string $domain;

    /**
     * @throws MailException
     */
    public function __construct(
        public readonly string $email,
        public readonly ?string $displayName = null,
    ) {
        if (\preg_match('/^([^\s@]+)@([^\s@]+)$/u', $email, $matches) !== 1) {
            throw MailException::fromInvalidEmail(
                email: $email,
            );
        }

        $length = \strlen($email);

        if ($length > self::EMAIL_MAX_LENGTH) {
            throw MailException::fromEmailTooLong(
                length: $length,
                limit: self::EMAIL_MAX_LENGTH,
            );
        }

        $localLength = \strlen($matches[1]);

        if ($localLength > self::LOCAL_PART_MAX_LENGTH) {
            throw MailException::fromLocalPartTooLong(
                length: $localLength,
                limit: self::LOCAL_PART_MAX_LENGTH,
            );
        }

        if (
            $displayName !== null &&
            \preg_match('/[\x00\r\n]/', $displayName) === 1
        ) {
            throw MailException::fromInvalidDisplayName();
        }

        $this->localPart = $matches[1];
        $this->domain = $matches[2];
    }

    /**
     * @throws MailException
     */
    #[\NoDiscard]
    public static function parse(
        string $raw,
    ): self {
        $trimmed = \trim($raw);

        if (\preg_match('/^(?<name>.*?)\s*<(?<email>[^<>]+)>$/u', $trimmed, $matches) === 1) {
            $namePart = \trim($matches['name']);
            $displayName = null;

            if ($namePart !== '') {
                if (\preg_match('/^"((?:[^"\\\\]|\\\\.)*)"$/u', $namePart, $quoted) === 1) {
                    $displayName = \str_replace(
                        [
                            '\\"',
                            '\\\\',
                        ],
                        [
                            '"',
                            '\\',
                        ],
                        $quoted[1],
                    );
                } else {
                    $displayName = $namePart;
                }
            }

            return new self(
                email: $matches['email'],
                displayName: $displayName,
            );
        }

        if (\preg_match('/^[^\s@]+@[^\s@]+$/u', $trimmed) === 1) {
            return new self(
                email: $trimmed,
            );
        }

        throw MailException::fromUnparseableAddress(
            raw: $raw,
        );
    }

    #[\NoDiscard]
    public function toRfc5322(): string
    {
        if ($this->displayName === null) {
            return $this->email;
        }

        if (\preg_match('/[^\x00-\x7F]/', $this->displayName) === 1) {
            return \sprintf(
                '%s <%s>',
                EncodedWord::encode($this->displayName),
                $this->email,
            );
        }

        return \sprintf(
            '"%s" <%s>',
            \str_replace(
                [
                    '\\',
                    '"',
                ],
                [
                    '\\\\',
                    '\\"',
                ],
                $this->displayName,
            ),
            $this->email,
        );
    }

    #[\NoDiscard]
    public function isInternationalized(): bool
    {
        return \preg_match('/[^\x00-\x7F]/', $this->email) === 1;
    }
}
