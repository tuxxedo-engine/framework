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

namespace Tuxxedo\Validator\Rule\NotSuspicious;

enum SpoofLevel: string
{
    case ASCII = 'ascii';
    case SINGLE_SCRIPT_RESTRICTIVE = 'single-script-restrictive';
    case HIGHLY_RESTRICTIVE = 'highly-restrictive';
    case MODERATELY_RESTRICTIVE = 'moderately-restrictive';
    case MINIMALLY_RESTRICTIVE = 'minimally-restrictive';
    case UNRESTRICTIVE = 'unrestrictive';
}
