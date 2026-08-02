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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Mail\Config\MailTransportConfigInterface;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpAuth;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTls;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTransportMode;
use Tuxxedo\Mail\Transport\SmtpMail\XoauthTokenProviderInterface;

#[DefaultImplementation(class: SmtpTransportConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface SmtpTransportConfigInterface extends MailTransportConfigInterface
{
    public string $host {
        get;
    }

    public int $port {
        get;
    }

    public SmtpTls $tls {
        get;
    }

    public SmtpAuth $auth {
        get;
    }

    public string $username {
        get;
    }

    public string $password {
        get;
    }

    public ?XoauthTokenProviderInterface $xoauthTokenProvider {
        get;
    }

    public int $connectTimeout {
        get;
    }

    public int $readTimeout {
        get;
    }

    public SmtpTransportMode $mode {
        get;
    }

    public ?string $ehloDomain {
        get;
    }

    public bool $verifyPeer {
        get;
    }

    public ?string $caFile {
        get;
    }
}
