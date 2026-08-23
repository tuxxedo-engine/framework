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

interface MessageInterface
{
    public AddressInterface $from {
        get;
    }

    /**
     * @var list<AddressInterface>
     */
    public array $to {
        get;
    }

    public string $subject {
        get;
    }

    public ?string $body {
        get;
    }

    public BodyType $bodyType {
        get;
    }

    public ?string $alternativeText {
        get;
    }

    /**
     * @var list<AddressInterface>
     */
    public array $cc {
        get;
    }

    /**
     * @var list<AddressInterface>
     */
    public array $bcc {
        get;
    }

    /**
     * @var list<AddressInterface>
     */
    public array $replyTo {
        get;
    }

    public ?AddressInterface $sender {
        get;
    }

    public ?AddressInterface $returnPath {
        get;
    }

    /**
     * @var list<AttachmentInterface>
     */
    public array $attachments {
        get;
    }

    /**
     * @var list<HeaderInterface>
     */
    public array $extraHeaders {
        get;
    }

    /**
     * @var non-empty-string
     */
    public string $messageId {
        get;
    }

    public \DateTimeImmutable $date {
        get;
    }

    /**
     * @throws MailException
     */
    public function withFrom(
        AddressInterface|string $from,
    ): MessageInterface;

    /**
     * @param list<AddressInterface|string>|AddressInterface|string $to
     *
     * @throws MailException
     */
    public function withTo(
        AddressInterface|string|array $to,
    ): MessageInterface;

    /**
     * @throws MailException
     */
    public function withSubject(
        string $subject,
    ): MessageInterface;

    /**
     * @throws MailException
     */
    public function withBody(
        ?string $body,
        BodyType $bodyType = BodyType::TEXT,
        ?string $alternativeText = null,
    ): MessageInterface;

    /**
     * @param list<AddressInterface|string>|AddressInterface|string $cc
     *
     * @throws MailException
     */
    public function withCc(
        AddressInterface|string|array $cc,
    ): MessageInterface;

    /**
     * @param list<AddressInterface|string>|AddressInterface|string $bcc
     *
     * @throws MailException
     */
    public function withBcc(
        AddressInterface|string|array $bcc,
    ): MessageInterface;

    /**
     * @param list<AddressInterface|string>|AddressInterface|string $replyTo
     *
     * @throws MailException
     */
    public function withReplyTo(
        AddressInterface|string|array $replyTo,
    ): MessageInterface;

    /**
     * @throws MailException
     */
    public function withSender(
        AddressInterface|string|null $sender,
    ): MessageInterface;

    /**
     * @throws MailException
     */
    public function withReturnPath(
        AddressInterface|string|null $returnPath,
    ): MessageInterface;

    /**
     * @throws MailException
     */
    public function withAttachment(
        AttachmentInterface $attachment,
    ): MessageInterface;

    /**
     * @param list<AttachmentInterface> $attachments
     *
     * @throws MailException
     */
    public function withAttachments(
        array $attachments,
    ): MessageInterface;

    /**
     * @throws MailException
     */
    public function withExtraHeader(
        HeaderInterface $header,
    ): MessageInterface;

    /**
     * @param list<HeaderInterface> $extraHeaders
     *
     * @throws MailException
     */
    public function withExtraHeaders(
        array $extraHeaders,
    ): MessageInterface;

    /**
     * @throws MailException
     */
    public function withoutExtraHeader(
        string $name,
    ): MessageInterface;
}
