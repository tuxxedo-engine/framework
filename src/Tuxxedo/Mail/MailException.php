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

    public static function fromSendMailFailure(
        int $exitCode,
        string $stderr,
    ): self {
        return new self(
            message: \sprintf(
                'sendmail exited with code %d: %s',
                $exitCode,
                \trim($stderr),
            ),
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

    public static function fromMissingIntlExtension(): self
    {
        return new self(
            message: 'The "intl" PHP extension is required to encode internationalized domain names (IDNA)',
        );
    }

    public static function fromIdnaConversionFailure(
        string $domain,
    ): self {
        return new self(
            message: \sprintf(
                'Failed to encode domain "%s" via IDNA (UTS #46)',
                $domain,
            ),
        );
    }

    public static function fromSmtpConnectionFailure(
        string $host,
        int $port,
        string $reason,
    ): self {
        return new self(
            message: \sprintf(
                'Failed to connect to SMTP server %s:%d: %s',
                $host,
                $port,
                $reason,
            ),
        );
    }

    public static function fromSmtpTlsHandshakeFailed(
        string $reason,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP TLS handshake failed: %s',
                $reason,
            ),
        );
    }

    public static function fromSmtpNotConnected(): self
    {
        return new self(
            message: 'SMTP socket operation attempted while not connected',
        );
    }

    public static function fromSmtpReadFailure(): self
    {
        return new self(
            message: 'Failed to read from SMTP socket',
        );
    }

    public static function fromSmtpReadTimeout(
        int $timeoutSeconds,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP socket read timed out after %d seconds',
                $timeoutSeconds,
            ),
        );
    }

    public static function fromSmtpWriteFailure(): self
    {
        return new self(
            message: 'Failed to write to SMTP socket',
        );
    }

    public static function fromSmtpMalformedResponse(
        string $line,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP server returned a malformed response line: "%s"',
                $line,
            ),
        );
    }

    public static function fromSmtpUnexpectedGreeting(
        int $code,
        string $summary,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP server did not send the expected 220 greeting: %d %s',
                $code,
                $summary,
            ),
        );
    }

    public static function fromSmtpHelloRejected(
        int $code,
        string $summary,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP server rejected the EHLO/HELO handshake: %d %s',
                $code,
                $summary,
            ),
        );
    }

    public static function fromSmtpCommandRejected(
        string $command,
        int $code,
        string $summary,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP server rejected "%s": %d %s',
                $command,
                $code,
                $summary,
            ),
        );
    }

    public static function fromSmtpMessageTooLarge(
        int $size,
        int $limit,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP message is too large: %d bytes exceeds the server-advertised SIZE limit of %d bytes',
                $size,
                $limit,
            ),
        );
    }

    public static function fromMailManagerConfiguratorMissingTransport(): self
    {
        return new self(
            message: 'MailManagerConfigurator cannot build a MailManager without a transport',
        );
    }

    public static function fromDkimInvalidPrivateKey(): self
    {
        return new self(
            message: 'Failed to parse DKIM RSA private key',
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromDkimSigningFailed(
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: 'DKIM signing failed',
            previous: $previous,
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromDkimMissingSodiumExtension(): self
    {
        return new self(
            message: 'The "sodium" PHP extension is required for Ed25519 DKIM signing',
        );
    }

    public static function fromDkimEd25519InvalidKey(): self
    {
        return new self(
            message: \sprintf(
                'DKIM Ed25519 private key must be a base64-encoded %d-byte seed',
                \SODIUM_CRYPTO_SIGN_SEEDBYTES,
            ),
        );
    }

    public static function fromSmtpStartTlsNotAdvertised(): self
    {
        return new self(
            message: 'SMTP server did not advertise STARTTLS but the configured TLS mode requires it',
        );
    }

    public static function fromSmtpAuthMechanismNotAdvertised(
        string $mechanism,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP server did not advertise the "%s" AUTH mechanism',
                $mechanism,
            ),
        );
    }

    public static function fromSmtpAuthFailed(
        string $mechanism,
        int $code,
        string $summary,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP authentication using "%s" failed: %d %s',
                $mechanism,
                $code,
                $summary,
            ),
        );
    }

    public static function fromSmtpXoauth2ProviderMissing(): self
    {
        return new self(
            message: 'SMTP XOAUTH2 authentication requires an XoauthTokenProviderInterface but none was configured',
        );
    }

    public static function fromSmtpAuthMalformedChallenge(
        string $challenge,
    ): self {
        return new self(
            message: \sprintf(
                'SMTP server sent a malformed AUTH challenge: "%s"',
                $challenge,
            ),
        );
    }

    public static function fromMissingMailTemplateAttribute(
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                'Mail template render was called on "%s" but the class has no #[MailTemplate] attribute',
                $className,
            ),
        );
    }

    public static function fromMailTemplateRenderFailed(
        string $className,
        string $templateName,
        \Throwable $previous,
    ): self {
        return new self(
            message: \sprintf(
                'Rendering mail template "%s" for class "%s" failed: %s',
                $templateName,
                $className,
                $previous->getMessage(),
            ),
            previous: $previous,
        );
    }

    public static function fromNoMailTemplateRendererRegistered(
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                'Message class "%s" has a #[MailTemplate] attribute but the MailManager has no MailTemplateRenderInterface configured',
                $className,
            ),
        );
    }
}
