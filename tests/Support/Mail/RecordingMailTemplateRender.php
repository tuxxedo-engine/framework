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

use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\MailTemplateRenderInterface;
use Tuxxedo\Mail\MessageInterface;

class RecordingMailTemplateRender implements MailTemplateRenderInterface
{
    /**
     * @var list<MessageInterface>
     */
    public array $seen = [];

    public function __construct(
        public readonly string $body = 'rendered-body',
        public readonly BodyType $bodyType = BodyType::HTML,
    ) {
    }

    public function render(
        MessageInterface $message,
    ): MessageInterface {
        $this->seen[] = $message;

        return $message->withBody(
            body: $this->body,
            bodyType: $this->bodyType,
        );
    }
}
