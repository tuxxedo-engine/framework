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

namespace Support\Mail;

use Tuxxedo\Mail\Attribute\MailTemplate;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\Message;

#[MailTemplate('stub', bodyType: BodyType::HTML)]
class StubTemplateMessage extends Message
{
    public function __construct(
        public readonly string $userName = 'stub-user',
    ) {
        parent::__construct(
            from: 'from@example.com',
            to: 'to@example.com',
            subject: 'Stub template',
        );
    }
}
