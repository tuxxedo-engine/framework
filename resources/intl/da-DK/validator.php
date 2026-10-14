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
    'common.wrong-type' => 'Værdien ved "{path}" skal være {expected}, fik {received}',
    'common.null-value' => 'Værdien ved "{path}" må ikke være null',
    'email.invalid-format' => 'Værdien ved "{path}" er ikke en gyldig e-mailadresse',
    'url.invalid-format' => 'Værdien ved "{path}" er ikke en gyldig URL',
    'url.disallowed-scheme' => 'URL\'en ved "{path}" bruger skemaet "{scheme}", tilladte skemaer er: {allowed}',
    'regex.no-match' => 'Værdien ved "{path}" matcher ikke det påkrævede mønster',
    'hostname.invalid-format' => 'Værdien ved "{path}" er ikke et gyldigt værtsnavn',
    'json.invalid-format' => 'Værdien ved "{path}" er ikke gyldig JSON',
    'base64.invalid-format' => 'Værdien ved "{path}" er ikke gyldig base64',
    'base64url.invalid-format' => 'Værdien ved "{path}" er ikke gyldig base64url',
    'prefixed-with.missing-prefix' => 'Værdien ved "{path}" skal starte med "{prefix}"',
    'suffixed-with.missing-suffix' => 'Værdien ved "{path}" skal slutte med "{suffix}"',
    'contains.missing-substring' => 'Værdien ved "{path}" skal indeholde "{needle}"',
    'length.too-short' => 'Værdien ved "{path}" skal være mindst {min} bytes lang, fik {actual}',
    'length.too-long' => 'Værdien ved "{path}" må højst være {max} bytes lang, fik {actual}',
    'character-length.too-short' => 'Værdien ved "{path}" skal være mindst {min} tegn lang, fik {actual}',
    'character-length.too-long' => 'Værdien ved "{path}" må højst være {max} tegn lang, fik {actual}',
    'positive-integer.not-positive' => 'Værdien ved "{path}" skal være et positivt heltal, fik {value}',
    'negative-integer.not-negative' => 'Værdien ved "{path}" skal være et negativt heltal, fik {value}',
    'range.below-min' => 'Værdien ved "{path}" ({actual}) er under minimum på {min}',
    'range.above-max' => 'Værdien ved "{path}" ({actual}) er over maksimum på {max}',
    'min.below-min' => 'Værdien ved "{path}" ({actual}) er under minimum på {min}',
    'max.above-max' => 'Værdien ved "{path}" ({actual}) er over maksimum på {max}',
    'not-empty.empty-value' => 'Værdien ved "{path}" må ikke være tom',
    'in.not-in-list' => 'Værdien ved "{path}" ({value}) skal være en af: {allowed}',
    'not-in.in-list' => 'Værdien ved "{path}" ({value}) må ikke være nogen af: {disallowed}',
    'equal-to.not-equal' => 'Værdien ved "{path}" ({value}) skal være lig med {expected}',
    'not-equal-to.equal' => 'Værdien ved "{path}" ({value}) må ikke være lig med {disallowed}',
    'date-time.invalid-format' => 'Værdien ved "{path}" er ikke en gyldig dato/tid',
    'duration.invalid-format' => 'Værdien ved "{path}" er ikke en gyldig ISO 8601-varighed ({reason})',
    'time-zone.unknown' => 'Værdien ved "{path}" ({received}) er ikke en kendt tidszone',
    'instant.not-before' => 'Værdien ved "{path}" skal være før {anchor}',
    'instant.not-after' => 'Værdien ved "{path}" skal være efter {anchor}',
    'period.inverted' => 'Perioden ved "{path}" er omvendt: slut ({end}) ligger før start ({start})',
    'uuid.invalid-format' => 'Værdien ved "{path}" er ikke et gyldigt UUID',
    'uuid-v4.invalid-format' => 'Værdien ved "{path}" er ikke et gyldigt UUID v4',
    'uuid-v7.invalid-format' => 'Værdien ved "{path}" er ikke et gyldigt UUID v7',
    'ulid.invalid-format' => 'Værdien ved "{path}" er ikke et gyldigt ULID',
    'ean.invalid-format' => 'Værdien ved "{path}" er ikke en gyldig EAN-stregkode',
    'ean.invalid-checksum' => 'Værdien ved "{path}" har en ugyldig EAN-kontrolsum',
    'ip.invalid-format' => 'Værdien ved "{path}" er ikke en gyldig IP-adresse',
    'ipv4.invalid-format' => 'Værdien ved "{path}" er ikke en gyldig IPv4-adresse',
    'ipv6.invalid-format' => 'Værdien ved "{path}" er ikke en gyldig IPv6-adresse',
    'alpha.not-alpha' => 'Værdien ved "{path}" må kun indeholde bogstaver',
    'alpha-numeric.not-alphanumeric' => 'Værdien ved "{path}" må kun indeholde bogstaver og cifre',
    'iban.invalid-format' => 'Værdien ved "{path}" er ikke et gyldigt IBAN',
    'iban.invalid-checksum' => 'Værdien ved "{path}" har en ugyldig IBAN-kontrolsum',
    'credit-card.invalid-format' => 'Værdien ved "{path}" er ikke et gyldigt kreditkortnummer',
    'credit-card.invalid-checksum' => 'Værdien ved "{path}" har en ugyldig kreditkortkontrolsum',
    'country-code.not-recognized' => 'Værdien ved "{path}" ({value}) er ikke en kendt ISO 3166-1 alpha-2-landekode',
    'language-code.not-recognized' => 'Værdien ved "{path}" ({value}) er ikke en kendt ISO 639-1-sprogkode',
    'enum.wrong-instance' => 'Værdien ved "{path}" skal være en instans af {expected}, fik {received}',
    'fallback' => 'Validering fejlede ved "{path}" ({code})',
];
