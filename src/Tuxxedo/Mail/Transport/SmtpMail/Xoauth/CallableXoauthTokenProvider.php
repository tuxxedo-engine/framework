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

namespace Tuxxedo\Mail\Transport\SmtpMail\Xoauth;

class CallableXoauthTokenProvider implements XoauthTokenProviderInterface
{
    /**
     * @param \Closure(): string $fetcher
     */
    public function __construct(
        private readonly \Closure $fetcher,
    ) {
    }

    public function getToken(): string
    {
        return ($this->fetcher)();
    }
}
