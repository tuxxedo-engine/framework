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

use Tuxxedo\Mail\Config\MailManagerConfig;
use Tuxxedo\Mail\Config\MailManagerConfigInterface;
use Tuxxedo\Mail\Transport\Config\PhpMailTransportConfig;

return static fn (): MailManagerConfigInterface => new MailManagerConfig(
    transport: new PhpMailTransportConfig(),
);
