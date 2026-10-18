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

return [
    'common.wrong-type' => 'Value at "{path}" must be {expected}, got {received}',
    'common.null-value' => 'Value at "{path}" may not be null',
    'email.invalid-format' => 'Value at "{path}" is not a valid email address',
    'url.invalid-format' => 'Value at "{path}" is not a valid URL',
    'url.disallowed-scheme' => 'URL at "{path}" uses scheme "{scheme}", allowed schemes are: {allowed}',
    'regex.no-match' => 'Value at "{path}" does not match the required pattern',
    'hostname.invalid-format' => 'Value at "{path}" is not a valid hostname',
    'json.invalid-format' => 'Value at "{path}" is not valid JSON',
    'base64.invalid-format' => 'Value at "{path}" is not valid base64',
    'base64url.invalid-format' => 'Value at "{path}" is not valid base64url',
    'prefixed-with.missing-prefix' => 'Value at "{path}" must start with "{prefix}"',
    'suffixed-with.missing-suffix' => 'Value at "{path}" must end with "{suffix}"',
    'contains.missing-substring' => 'Value at "{path}" must contain "{needle}"',
    'length.too-short' => 'Value at "{path}" must be at least {min} bytes long, got {actual}',
    'length.too-long' => 'Value at "{path}" must be no more than {max} bytes long, got {actual}',
    'character-length.too-short' => 'Value at "{path}" must be at least {min} characters long, got {actual}',
    'character-length.too-long' => 'Value at "{path}" must be no more than {max} characters long, got {actual}',
    'positive-integer.not-positive' => 'Value at "{path}" must be a positive integer, got {value}',
    'negative-integer.not-negative' => 'Value at "{path}" must be a negative integer, got {value}',
    'range.below-min' => 'Value at "{path}" ({actual}) is below the minimum of {min}',
    'range.above-max' => 'Value at "{path}" ({actual}) is above the maximum of {max}',
    'min.below-min' => 'Value at "{path}" ({actual}) is below the minimum of {min}',
    'max.above-max' => 'Value at "{path}" ({actual}) is above the maximum of {max}',
    'not-empty.empty-value' => 'Value at "{path}" may not be empty',
    'in.not-in-list' => 'Value at "{path}" ({value}) must be one of: {allowed}',
    'not-in.in-list' => 'Value at "{path}" ({value}) may not be any of: {disallowed}',
    'equal-to.not-equal' => 'Value at "{path}" ({value}) must equal {expected}',
    'not-equal-to.equal' => 'Value at "{path}" ({value}) may not equal {disallowed}',
    'date-time.invalid-format' => 'Value at "{path}" is not a valid date/time',
    'duration.invalid-format' => 'Value at "{path}" is not a valid ISO 8601 duration ({reason})',
    'time-zone.unknown' => 'Value at "{path}" ({received}) is not a recognized time zone',
    'instant.not-before' => 'Value at "{path}" must be before {anchor}',
    'instant.not-after' => 'Value at "{path}" must be after {anchor}',
    'period.inverted' => 'Period at "{path}" is inverted: end ({end}) precedes start ({start})',
    'uuid.invalid-format' => 'Value at "{path}" is not a valid UUID',
    'uuid-v4.invalid-format' => 'Value at "{path}" is not a valid UUID v4',
    'uuid-v7.invalid-format' => 'Value at "{path}" is not a valid UUID v7',
    'ulid.invalid-format' => 'Value at "{path}" is not a valid ULID',
    'ean.invalid-format' => 'Value at "{path}" is not a valid EAN barcode',
    'ean.invalid-checksum' => 'Value at "{path}" has an invalid EAN checksum',
    'ip.invalid-format' => 'Value at "{path}" is not a valid IP address',
    'ipv4.invalid-format' => 'Value at "{path}" is not a valid IPv4 address',
    'ipv6.invalid-format' => 'Value at "{path}" is not a valid IPv6 address',
    'alpha.not-alpha' => 'Value at "{path}" must contain only letters',
    'alpha-numeric.not-alphanumeric' => 'Value at "{path}" must contain only letters and digits',
    'not-suspicious.suspicious' => 'Value at "{path}" contains characters that can be mistaken for others',
    'iban.invalid-format' => 'Value at "{path}" is not a valid IBAN',
    'iban.invalid-checksum' => 'Value at "{path}" has an invalid IBAN checksum',
    'credit-card.invalid-format' => 'Value at "{path}" is not a valid credit card number',
    'credit-card.invalid-checksum' => 'Value at "{path}" has an invalid credit card checksum',
    'country-code.not-recognized' => 'Value at "{path}" ({value}) is not a recognized ISO 3166-1 alpha-2 country code',
    'language-code.not-recognized' => 'Value at "{path}" ({value}) is not a recognized ISO 639-1 language code',
    'enum.wrong-instance' => 'Value at "{path}" must be an instance of {expected}, got {received}',
    'fallback' => 'Validation failed at "{path}" ({code})',
];
