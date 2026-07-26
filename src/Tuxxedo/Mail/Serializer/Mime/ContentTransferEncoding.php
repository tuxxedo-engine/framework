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

namespace Tuxxedo\Mail\Serializer\Mime;

enum ContentTransferEncoding: string
{
    case SEVEN_BIT = '7bit';
    case EIGHT_BIT = '8bit';
    case QUOTED_PRINTABLE = 'quoted-printable';
    case BASE64 = 'base64';
}
