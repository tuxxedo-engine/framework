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

namespace Tuxxedo\Mail\Config;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Mail\MailTemplateRenderInterface;

interface MailTemplateConfigInterface
{
    public function createTemplateRender(
        ContainerInterface $container,
    ): MailTemplateRenderInterface;
}
