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

namespace Tuxxedo\Mail\Transport\Smtp;

enum SmtpTransportMode: string
{
    case PER_MESSAGE = 'per-message';
    case REUSE_CONNECTION = 'reuse-connection';
}
