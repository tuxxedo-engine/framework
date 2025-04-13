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

namespace Tuxxedo\Http\Request;

use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\WeightedHeaderInterface;
use Tuxxedo\Net\IpAddressInterface;

interface ServerEnvironmentInterface
{
    public function getHttpAccept(): WeightedHeaderInterface;
    public function getHttpAcceptCharset(): WeightedHeaderInterface;
    public function getHttpAcceptLanguage(): WeightedHeaderInterface;
    public function getHttpUserAgent(): HeaderInterface;

    public function getRemoteAddr(): IpAddressInterface;
    public function getServerName(): string;

    /**
     * @return positive-int
     */
    public function getServerPort(): int;
}
