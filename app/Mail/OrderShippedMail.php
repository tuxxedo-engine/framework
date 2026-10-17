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

namespace App\Mail;

use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\Mail\Attribute\MailTemplate;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\LocalizedMessageInterface;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\TimeZonedMessageInterface;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\TimeZoneInterface;

#[MailTemplate('order_shipped', bodyType: BodyType::HTML)]
class OrderShippedMail extends Message implements LocalizedMessageInterface, TimeZonedMessageInterface
{
    public function __construct(
        TranslatorInterface $translator,
        string $to,
        public readonly LocaleInterface $locale,
        public readonly TimeZoneInterface $timeZone,
        public readonly string $recipientName,
        public readonly string $orderNumber,
        public readonly int $itemCount,
        public readonly float $total,
        public readonly InstantInterface $shippedAt,
        public readonly string $trackingUrl,
    ) {
        parent::__construct(
            from: 'shop@example.com',
            to: $to,
            subject: $translator->translate(
                key: 'mail.orderShipped.subject',
                replacements: [
                    'order' => $orderNumber,
                ],
                locale: $locale,
            ),
        );
    }
}
