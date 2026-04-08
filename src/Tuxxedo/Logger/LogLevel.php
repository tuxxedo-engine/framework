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

namespace Tuxxedo\Logger;

enum LogLevel
{
    case ALERT;
    case CRITICAL;
    case DEBUG;
    case EMERGENCY;
    case ERROR;
    case INFO;
    case NOTICE;
    case WARNING;
}
