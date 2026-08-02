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

namespace Tuxxedo\Mail\Signer\Dkim;

enum DkimAlgorithm: string
{
    case RSA_SHA256 = 'rsa-sha256';
    case ED25519_SHA256 = 'ed25519-sha256';
}
