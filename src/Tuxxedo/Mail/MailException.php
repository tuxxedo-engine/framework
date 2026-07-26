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

class MailException extends \Exception
{
    public static function fromInvalidEmail(
        string $email,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid email address "%s"',
                $email,
            ),
        );
    }

    public static function fromEmailTooLong(
        int $length,
        int $limit,
    ): self {
        return new self(
            message: \sprintf(
                'Email address is too long: %d bytes exceeds the limit of %d bytes',
                $length,
                $limit,
            ),
        );
    }

    public static function fromLocalPartTooLong(
        int $length,
        int $limit,
    ): self {
        return new self(
            message: \sprintf(
                'Email local-part is too long: %d bytes exceeds the limit of %d bytes',
                $length,
                $limit,
            ),
        );
    }

    public static function fromInvalidDisplayName(): self
    {
        return new self(
            message: 'Display name contains disallowed control characters',
        );
    }

    public static function fromUnparseableAddress(
        string $raw,
    ): self {
        return new self(
            message: \sprintf(
                'Could not parse address "%s"',
                $raw,
            ),
        );
    }
}
