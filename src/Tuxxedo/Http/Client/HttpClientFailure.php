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

namespace Tuxxedo\Http\Client;

enum HttpClientFailure
{
    case STATUS;
    case BODY;
    case TRANSPORT;
    case TIMEOUT;
    case PROTOCOL;
    case REDIRECT;
    case SIZE;
    case RULE;
    case REPLAY;
    case REQUEST;
    case AUTH;
    case ABORTED;
}
