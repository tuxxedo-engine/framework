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

namespace Tuxxedo;

class Version
{
    final public const string SIMPLE = '0.1.0';

    final public const int MAJOR = 0;

    final public const int MINOR = 1;

    final public const int RELEASE = 0;

    final public const int ID = 100;

    final public const bool PREVIEW = true;

    final public const string PREVIEW_TYPE = 'Alpha';

    final public const int PREVIEW_NUMBER = 1;

    final public const string CODENAME = 'enginepl';

    final public const string FULL = '0.1.0 "enginepl" Alpha 1 (experimental)';
}
