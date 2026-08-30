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

use Tuxxedo\Mail\Attribute\MailTemplate;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\Message;

#[MailTemplate('welcome', bodyType: BodyType::HTML)]
class WelcomeMail extends Message
{
    public function __construct(
        string $to,
        public readonly string $recipientName,
        public readonly string $activationUrl,
    ) {
        parent::__construct(
            from: 'demo@example.com',
            to: $to,
            subject: 'Welcome to Engine',
        );
    }
}
