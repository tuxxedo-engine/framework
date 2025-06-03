<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Http;

enum HttpVersion: string
{
    case V0_9 = 'HTTP/0.9';
    case V1_0 = 'HTTP/1.0';
    case V1_1 = 'HTTP/1.1';
    case V2_0 = 'HTTP/2.0';
    case V3_0 = 'HTTP/3.0';
}
