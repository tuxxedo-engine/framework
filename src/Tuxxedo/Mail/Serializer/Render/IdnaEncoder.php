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

namespace Tuxxedo\Mail\Serializer\Render;

use Tuxxedo\Mail\MailException;

class IdnaEncoder
{
    /**
     * @throws MailException
     */
    public static function encode(
        string $domain,
    ): string {
        if (\preg_match('/[^\x00-\x7F]/', $domain) !== 1) {
            return $domain;
        }

        if (!\function_exists('idn_to_ascii')) {
            throw MailException::fromMissingIntlExtension();
        }

        $encoded = \idn_to_ascii($domain, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46);

        if ($encoded === false) {
            throw MailException::fromIdnaConversionFailure(
                domain: $domain,
            );
        }

        return $encoded;
    }
}
