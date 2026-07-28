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

    public static function fromInvalidContentId(): self
    {
        return new self(
            message: 'Content-ID contains disallowed control characters',
        );
    }

    public static function fromInvalidDescription(): self
    {
        return new self(
            message: 'Attachment description contains disallowed control characters',
        );
    }

    public static function fromInvalidHeaderName(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid header name "%s"',
                $name,
            ),
        );
    }

    public static function fromInvalidHeaderValue(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Header "%s" has an invalid value (contains disallowed control characters)',
                $name,
            ),
        );
    }

    public static function fromReservedHeaderName(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Header "%s" is reserved by the framework and cannot appear in extraHeaders',
                $name,
            ),
        );
    }

    public static function fromAlternativeTextRequiresHtmlBody(): self
    {
        return new self(
            message: 'alternativeText may only be set when bodyType is BodyType::HTML',
        );
    }

    public static function fromAttachmentReadFailure(
        ?string $filename,
        \Throwable $previous,
    ): self {
        return new self(
            message: \sprintf(
                'Failed to read attachment "%s"',
                $filename ?? '(unnamed)',
            ),
            previous: $previous,
        );
    }

    /**
     * @param class-string<Transport\MailTransportInterface> $transport
     */
    public static function fromBccNotSupportedByTransport(
        string $transport,
    ): self {
        return new self(
            message: \sprintf(
                'Transport "%s" does not support Bcc recipients',
                $transport,
            ),
        );
    }

    /**
     * @param class-string<Transport\MailTransportInterface> $transport
     */
    public static function fromTransportFailure(
        string $transport,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: \sprintf(
                'Transport "%s" failed to deliver the message',
                $transport,
            ),
            previous: $previous,
        );
    }

    /**
     * @param class-string $mimePartClass
     */
    public static function fromNonSerializableMimePart(
        string $mimePartClass,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to serialize mime part object: %s',
                $mimePartClass,
            ),
        );
    }
}
