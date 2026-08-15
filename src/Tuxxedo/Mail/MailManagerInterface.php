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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Mail\Result\SendResultInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;

// @todo Consider how templating could work for mails, Lumi integration? Others? This may need LumiEngine to be preconfigured to different backend use-cases and better configurability, which it currently does not support
// @todo Test cases for Mail
#[DefaultImplementation(class: MailManager::class, lifecycle: Lifecycle::SINGLETON)]
interface MailManagerInterface
{
    public MailTransportInterface $transport {
        get;
    }

    /**
     * @throws MailException
     */
    public function send(
        MessageInterface ...$messages,
    ): void;

    /**
     * @return list<SendResultInterface>
     *
     * @throws MailException
     */
    public function sendWithResult(
        MessageInterface ...$messages,
    ): array;
}
