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

class Message implements MessageInterface
{
    private const array RESERVED_HEADER_NAMES = [
        'from',
        'sender',
        'reply-to',
        'return-path',
        'to',
        'cc',
        'bcc',
        'subject',
        'message-id',
        'date',
        'mime-version',
    ];

    public readonly AddressInterface $from;

    /**
     * @var list<AddressInterface>
     */
    public readonly array $to;

    /**
     * @var list<AddressInterface>
     */
    public readonly array $cc;

    /**
     * @var list<AddressInterface>
     */
    public readonly array $bcc;

    /**
     * @var list<AddressInterface>
     */
    public readonly array $replyTo;

    public readonly ?AddressInterface $sender;
    public readonly ?AddressInterface $returnPath;
    public readonly string $messageId;
    public readonly \DateTimeImmutable $date;

    /**
     * @param list<AddressInterface|string>|AddressInterface|string $to
     * @param list<AddressInterface|string>|AddressInterface|string $cc
     * @param list<AddressInterface|string>|AddressInterface|string $bcc
     * @param list<AddressInterface|string>|AddressInterface|string $replyTo
     * @param list<AttachmentInterface> $attachments
     * @param list<HeaderInterface> $extraHeaders
     *
     * @throws MailException
     */
    public function __construct(
        AddressInterface|string $from,
        AddressInterface|string|array $to,
        public readonly string $subject,
        public readonly ?string $body = null,
        public readonly BodyType $bodyType = BodyType::TEXT,
        public readonly ?string $alternativeText = null,
        AddressInterface|string|array $cc = [],
        AddressInterface|string|array $bcc = [],
        AddressInterface|string|array $replyTo = [],
        AddressInterface|string|null $sender = null,
        AddressInterface|string|null $returnPath = null,
        public readonly array $attachments = [],
        public readonly array $extraHeaders = [],
        ?string $messageId = null,
        ?\DateTimeImmutable $date = null,
    ) {
        if (
            $alternativeText !== null &&
            $bodyType !== BodyType::HTML
        ) {
            throw MailException::fromAlternativeTextRequiresHtmlBody();
        }

        foreach ($extraHeaders as $header) {
            if (\in_array(\strtolower($header->name), self::RESERVED_HEADER_NAMES, true)) {
                throw MailException::fromReservedHeaderName($header->name);
            }
        }

        $this->from = self::coerceAddress($from);
        $this->to = self::coerceAddressList($to);
        $this->cc = self::coerceAddressList($cc);
        $this->bcc = self::coerceAddressList($bcc);
        $this->replyTo = self::coerceAddressList($replyTo);
        $this->sender = $sender !== null
            ? self::coerceAddress($sender)
            : null;

        $this->returnPath = $returnPath !== null
            ? self::coerceAddress($returnPath)
            : null;

        $this->messageId = $messageId ?? self::generateMessageId($this->from);
        $this->date = $date ?? new \DateTimeImmutable();
    }

    /**
     * @throws MailException
     */
    private static function coerceAddress(
        AddressInterface|string $value,
    ): AddressInterface {
        return $value instanceof AddressInterface
            ? $value
            : new Address($value);
    }

    /**
     * @param list<AddressInterface|string>|AddressInterface|string $value
     * @return list<AddressInterface>
     *
     * @throws MailException
     */
    private static function coerceAddressList(
        AddressInterface|string|array $value,
    ): array {
        if (\is_string($value) || $value instanceof AddressInterface) {
            return [
                self::coerceAddress($value),
            ];
        }

        $result = [];

        foreach ($value as $item) {
            $result[] = self::coerceAddress($item);
        }

        return $result;
    }

    private static function generateMessageId(
        AddressInterface $from,
    ): string {
        return \sprintf(
            '<%s@%s>',
            \bin2hex(\random_bytes(16)),
            $from->domain,
        );
    }
}
