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

namespace Tuxxedo\Validator\Rule\CurrencyCode;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class CurrencyCodeRule implements RuleInterface
{
    /**
     * @var list<string>
     */
    private const array CODES = [
        'AED',
        'AFN',
        'ALL',
        'AMD',
        'AOA',
        'ARS',
        'AUD',
        'AWG',
        'AZN',
        'BAM',
        'BBD',
        'BDT',
        'BHD',
        'BIF',
        'BMD',
        'BND',
        'BOB',
        'BOV',
        'BRL',
        'BSD',
        'BTN',
        'BWP',
        'BYN',
        'BZD',
        'CAD',
        'CDF',
        'CHE',
        'CHF',
        'CHW',
        'CLF',
        'CLP',
        'CNY',
        'COP',
        'COU',
        'CRC',
        'CUP',
        'CVE',
        'CZK',
        'DJF',
        'DKK',
        'DOP',
        'DZD',
        'EGP',
        'ERN',
        'ETB',
        'EUR',
        'FJD',
        'FKP',
        'GBP',
        'GEL',
        'GHS',
        'GIP',
        'GMD',
        'GNF',
        'GTQ',
        'GYD',
        'HKD',
        'HNL',
        'HTG',
        'HUF',
        'IDR',
        'ILS',
        'INR',
        'IQD',
        'IRR',
        'ISK',
        'JMD',
        'JOD',
        'JPY',
        'KES',
        'KGS',
        'KHR',
        'KMF',
        'KPW',
        'KRW',
        'KWD',
        'KYD',
        'KZT',
        'LAK',
        'LBP',
        'LKR',
        'LRD',
        'LSL',
        'LYD',
        'MAD',
        'MDL',
        'MGA',
        'MKD',
        'MMK',
        'MNT',
        'MOP',
        'MRU',
        'MUR',
        'MVR',
        'MWK',
        'MXN',
        'MXV',
        'MYR',
        'MZN',
        'NAD',
        'NGN',
        'NIO',
        'NOK',
        'NPR',
        'NZD',
        'OMR',
        'PAB',
        'PEN',
        'PGK',
        'PHP',
        'PKR',
        'PLN',
        'PYG',
        'QAR',
        'RON',
        'RSD',
        'RUB',
        'RWF',
        'SAR',
        'SBD',
        'SCR',
        'SDG',
        'SEK',
        'SGD',
        'SHP',
        'SLE',
        'SOS',
        'SRD',
        'SSP',
        'STN',
        'SVC',
        'SYP',
        'SZL',
        'THB',
        'TJS',
        'TMT',
        'TND',
        'TOP',
        'TRY',
        'TTD',
        'TWD',
        'TZS',
        'UAH',
        'UGX',
        'USD',
        'USN',
        'UYI',
        'UYU',
        'UYW',
        'UZS',
        'VED',
        'VES',
        'VND',
        'VUV',
        'WST',
        'XAF',
        'XAG',
        'XAU',
        'XBA',
        'XBB',
        'XBC',
        'XBD',
        'XCD',
        'XCG',
        'XDR',
        'XOF',
        'XPD',
        'XPF',
        'XPT',
        'XSU',
        'XTS',
        'XUA',
        'XXX',
        'YER',
        'ZAR',
        'ZMW',
        'ZWG',
    ];

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            return new Violation(
                code: CommonViolationCode::WRONG_TYPE,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new WrongTypeViolationContext(
                    expected: 'string',
                    received: \get_debug_type($value),
                ),
            );
        }

        if (!\in_array(\strtoupper($value), self::CODES, true)) {
            return new Violation(
                code: CurrencyCodeViolationCode::NOT_RECOGNIZED,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        return null;
    }
}
