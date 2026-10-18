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
    'common.wrong-type' => 'Värdet vid "{path}" måste vara {expected}, fick {received}',
    'common.null-value' => 'Värdet vid "{path}" får inte vara null',
    'email.invalid-format' => 'Värdet vid "{path}" är inte en giltig e-postadress',
    'url.invalid-format' => 'Värdet vid "{path}" är inte en giltig URL',
    'url.disallowed-scheme' => 'URL:en vid "{path}" använder schemat "{scheme}", tillåtna scheman är: {allowed}',
    'regex.no-match' => 'Värdet vid "{path}" matchar inte det obligatoriska mönstret',
    'hostname.invalid-format' => 'Värdet vid "{path}" är inte ett giltigt värdnamn',
    'json.invalid-format' => 'Värdet vid "{path}" är inte giltig JSON',
    'base64.invalid-format' => 'Värdet vid "{path}" är inte giltig base64',
    'base64url.invalid-format' => 'Värdet vid "{path}" är inte giltig base64url',
    'prefixed-with.missing-prefix' => 'Värdet vid "{path}" måste börja med "{prefix}"',
    'suffixed-with.missing-suffix' => 'Värdet vid "{path}" måste sluta med "{suffix}"',
    'contains.missing-substring' => 'Värdet vid "{path}" måste innehålla "{needle}"',
    'length.too-short' => 'Värdet vid "{path}" måste vara minst {min} byte långt, fick {actual}',
    'length.too-long' => 'Värdet vid "{path}" får vara högst {max} byte långt, fick {actual}',
    'character-length.too-short' => 'Värdet vid "{path}" måste vara minst {min} tecken långt, fick {actual}',
    'character-length.too-long' => 'Värdet vid "{path}" får vara högst {max} tecken långt, fick {actual}',
    'positive-integer.not-positive' => 'Värdet vid "{path}" måste vara ett positivt heltal, fick {value}',
    'negative-integer.not-negative' => 'Värdet vid "{path}" måste vara ett negativt heltal, fick {value}',
    'range.below-min' => 'Värdet vid "{path}" ({actual}) är under minimum {min}',
    'range.above-max' => 'Värdet vid "{path}" ({actual}) är över maximum {max}',
    'min.below-min' => 'Värdet vid "{path}" ({actual}) är under minimum {min}',
    'max.above-max' => 'Värdet vid "{path}" ({actual}) är över maximum {max}',
    'not-empty.empty-value' => 'Värdet vid "{path}" får inte vara tomt',
    'in.not-in-list' => 'Värdet vid "{path}" ({value}) måste vara ett av: {allowed}',
    'not-in.in-list' => 'Värdet vid "{path}" ({value}) får inte vara något av: {disallowed}',
    'equal-to.not-equal' => 'Värdet vid "{path}" ({value}) måste vara lika med {expected}',
    'not-equal-to.equal' => 'Värdet vid "{path}" ({value}) får inte vara lika med {disallowed}',
    'date-time.invalid-format' => 'Värdet vid "{path}" är inte ett giltigt datum/tid',
    'duration.invalid-format' => 'Värdet vid "{path}" är inte en giltig ISO 8601-varaktighet ({reason})',
    'time-zone.unknown' => 'Värdet vid "{path}" ({received}) är inte en känd tidszon',
    'instant.not-before' => 'Värdet vid "{path}" måste vara före {anchor}',
    'instant.not-after' => 'Värdet vid "{path}" måste vara efter {anchor}',
    'period.inverted' => 'Perioden vid "{path}" är omvänd: slut ({end}) ligger före start ({start})',
    'uuid.invalid-format' => 'Värdet vid "{path}" är inte ett giltigt UUID',
    'uuid-v4.invalid-format' => 'Värdet vid "{path}" är inte ett giltigt UUID v4',
    'uuid-v7.invalid-format' => 'Värdet vid "{path}" är inte ett giltigt UUID v7',
    'ulid.invalid-format' => 'Värdet vid "{path}" är inte ett giltigt ULID',
    'ean.invalid-format' => 'Värdet vid "{path}" är inte en giltig EAN-streckkod',
    'ean.invalid-checksum' => 'Värdet vid "{path}" har en ogiltig EAN-kontrollsumma',
    'ip.invalid-format' => 'Värdet vid "{path}" är inte en giltig IP-adress',
    'ipv4.invalid-format' => 'Värdet vid "{path}" är inte en giltig IPv4-adress',
    'ipv6.invalid-format' => 'Värdet vid "{path}" är inte en giltig IPv6-adress',
    'alpha.not-alpha' => 'Värdet vid "{path}" får endast innehålla bokstäver',
    'alpha-numeric.not-alphanumeric' => 'Värdet vid "{path}" får endast innehålla bokstäver och siffror',
    'not-suspicious.suspicious' => 'Värdet vid "{path}" innehåller tecken som kan förväxlas med andra',
    'iban.invalid-format' => 'Värdet vid "{path}" är inte ett giltigt IBAN',
    'iban.invalid-checksum' => 'Värdet vid "{path}" har en ogiltig IBAN-kontrollsumma',
    'credit-card.invalid-format' => 'Värdet vid "{path}" är inte ett giltigt kreditkortsnummer',
    'credit-card.invalid-checksum' => 'Värdet vid "{path}" har en ogiltig kreditkortskontrollsumma',
    'country-code.not-recognized' => 'Värdet vid "{path}" ({value}) är inte en känd ISO 3166-1 alpha-2-landskod',
    'language-code.not-recognized' => 'Värdet vid "{path}" ({value}) är inte en känd ISO 639-1-språkkod',
    'enum.wrong-instance' => 'Värdet vid "{path}" måste vara en instans av {expected}, fick {received}',
    'fallback' => 'Valideringen misslyckades vid "{path}" ({code})',
];
