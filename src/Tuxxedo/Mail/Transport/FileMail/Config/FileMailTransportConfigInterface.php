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

namespace Tuxxedo\Mail\Transport\FileMail\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Mail\Config\MailTransportConfigInterface;

#[DefaultImplementation(class: FileMailTransportConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface FileMailTransportConfigInterface extends MailTransportConfigInterface
{
    public string $directory {
        get;
    }
}
