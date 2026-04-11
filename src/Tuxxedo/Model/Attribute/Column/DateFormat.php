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

namespace Tuxxedo\Model\Attribute\Column;

enum DateFormat: string
{
    case DEFAULT = 'Y-m-d';
    case UNIX = 'U';
    case US = 'm/d/Y';
    case EUROPEAN = 'd/m/Y';
}
