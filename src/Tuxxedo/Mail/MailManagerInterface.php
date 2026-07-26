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
use Tuxxedo\Mail\Transport\MailTransportInterface;

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
}
