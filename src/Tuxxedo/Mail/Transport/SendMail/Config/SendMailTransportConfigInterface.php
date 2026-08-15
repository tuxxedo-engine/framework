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

namespace Tuxxedo\Mail\Transport\SendMail\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Mail\Config\MailTransportConfigInterface;

#[DefaultImplementation(class: SendMailTransportConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface SendMailTransportConfigInterface extends MailTransportConfigInterface
{
    public string $binary {
        get;
    }

    /**
     * @var list<string>
     */
    public array $arguments {
        get;
    }

    public ?int $timeoutSeconds {
        get;
    }
}
