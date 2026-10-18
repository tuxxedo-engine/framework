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

namespace App\Input;

use Tuxxedo\Validator\Rule\CountryCode\CountryCodeRule;
use Tuxxedo\Validator\Rule\In\InRule;
use Tuxxedo\Validator\Rule\Locale\LocaleRule;

class TimezonePickerInput
{
    public const array LOCALES = [
        'en-US',
        'da-DK',
        'pt-BR',
        'cs-CZ',
    ];

    public function __construct(
        #[LocaleRule]
        #[InRule(self::LOCALES)]
        public readonly string $locale,
        #[CountryCodeRule]
        public readonly ?string $country,
    ) {
    }
}
