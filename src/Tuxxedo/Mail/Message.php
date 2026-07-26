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

/**
 * @todo Consider $extraHeaders, but decide whether SMTP headers share an abstraction with HTTP headers first
 * @todo Implement $attachments
 * @todo Consider whether there should be one $body instead of a separate Text and HTML variants. Lumi support?
 */
class Message implements MessageInterface
{
    public readonly string $messageId;
    public readonly \DateTimeImmutable $date;

    /**
     * @param list<AddressInterface> $to
     * @param list<AddressInterface> $cc
     * @param list<AddressInterface> $bcc
     * @param list<AddressInterface> $replyTo
     */
    public function __construct(
        public readonly AddressInterface $from,
        public readonly array $to,
        public readonly string $subject,
        public readonly ?string $textBody = null,
        public readonly ?string $htmlBody = null,
        public readonly array $cc = [],
        public readonly array $bcc = [],
        public readonly array $replyTo = [],
        public readonly ?AddressInterface $sender = null,
        public readonly ?AddressInterface $returnPath = null,
        ?string $messageId = null,
        ?\DateTimeImmutable $date = null,
    ) {
        $this->messageId = $messageId ?? self::generateMessageId(
            from: $from,
        );

        $this->date = $date ?? new \DateTimeImmutable();
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
