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

use Tuxxedo\Validator\Rule\CurrencyCode\CurrencyCodeRule;
use Tuxxedo\Validator\Rule\Email\EmailRule;
use Tuxxedo\Validator\Rule\In\InRule;
use Tuxxedo\Validator\Rule\Locale\LocaleRule;
use Tuxxedo\Validator\Rule\TimeZoneIdentifier\TimeZoneIdentifierRule;

class OrderShippedMailInput
{
    public const array LANGUAGES = [
        'en-US',
        'pt-PT',
        'pt-BR',
    ];

    public const array CURRENCIES = [
        'EUR',
        'USD',
        'BRL',
        'DKK',
    ];

    public function __construct(
        #[EmailRule]
        public readonly string $to,
        public readonly string $recipientName,
        #[LocaleRule]
        #[InRule(self::LANGUAGES)]
        public readonly string $language,
        #[TimeZoneIdentifierRule]
        public readonly string $timeZone,
        #[CurrencyCodeRule]
        public readonly string $currency,
        public readonly int $itemCount,
    ) {
    }
}
