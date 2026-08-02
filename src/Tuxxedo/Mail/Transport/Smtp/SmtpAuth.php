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

enum SmtpAuth: string
{
    case NONE = 'NONE';
    case PLAIN = 'PLAIN';
    case LOGIN = 'LOGIN';
    case CRAM_MD5 = 'CRAM-MD5';
    case XOAUTH2 = 'XOAUTH2';
}
