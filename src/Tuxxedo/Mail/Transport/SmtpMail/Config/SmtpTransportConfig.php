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

namespace Tuxxedo\Mail\Transport\SmtpMail\Config;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpAuth;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTls;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTransport;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTransportMode;
use Tuxxedo\Mail\Transport\SmtpMail\XoauthTokenProviderInterface;

class SmtpTransportConfig implements SmtpTransportConfigInterface
{
    public function __construct(
        public readonly string $host = 'localhost',
        public readonly int $port = 587,
        public readonly SmtpTls $tls = SmtpTls::STARTTLS,
        public readonly SmtpAuth $auth = SmtpAuth::NONE,
        public readonly string $username = '',
        #[\SensitiveParameter] public readonly string $password = '',
        public readonly ?XoauthTokenProviderInterface $xoauthTokenProvider = null,
        public readonly int $connectTimeout = 30,
        public readonly int $readTimeout = 30,
        public readonly SmtpTransportMode $mode = SmtpTransportMode::REUSE_CONNECTION,
        public readonly int $reuseLimit = 0,
        public readonly ?string $ehloDomain = null,
        public readonly bool $verifyPeer = true,
        public readonly ?string $caFile = null,
    ) {
    }

    public function createTransport(
        ContainerInterface $container,
    ): MailTransportInterface {
        return $container->resolve(SmtpTransport::class);
    }
}
