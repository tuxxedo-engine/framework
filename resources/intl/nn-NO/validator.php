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
    'common.wrong-type' => 'Verdien ved "{path}" må vere {expected}, fekk {received}',
    'common.null-value' => 'Verdien ved "{path}" kan ikkje vere null',
    'common.invalid-encoding' => 'Verdien ved "{path}" er ikkje gyldig tekst',
    'email.invalid-format' => 'Verdien ved "{path}" er ikkje ei gyldig e-postadresse',
    'url.invalid-format' => 'Verdien ved "{path}" er ikkje ein gyldig URL',
    'url.disallowed-scheme' => 'URL-en ved "{path}" brukar skjemaet "{scheme}", tillatne skjema er: {allowed}',
    'regex.no-match' => 'Verdien ved "{path}" samsvarer ikkje med det påkravde mønsteret',
    'hostname.invalid-format' => 'Verdien ved "{path}" er ikkje eit gyldig vertsnamn',
    'json.invalid-format' => 'Verdien ved "{path}" er ikkje gyldig JSON',
    'base64.invalid-format' => 'Verdien ved "{path}" er ikkje gyldig base64',
    'base64url.invalid-format' => 'Verdien ved "{path}" er ikkje gyldig base64url',
    'prefixed-with.missing-prefix' => 'Verdien ved "{path}" må starte med "{prefix}"',
    'suffixed-with.missing-suffix' => 'Verdien ved "{path}" må slutte med "{suffix}"',
    'contains.missing-substring' => 'Verdien ved "{path}" må innehalde "{needle}"',
    'length.too-short' => 'Verdien ved "{path}" må vere minst {min} byte lang, fekk {actual}',
    'length.too-long' => 'Verdien ved "{path}" kan vere høgst {max} byte lang, fekk {actual}',
    'character-length.too-short' => 'Verdien ved "{path}" må vere minst {min} teikn lang, fekk {actual}',
    'character-length.too-long' => 'Verdien ved "{path}" kan vere høgst {max} teikn lang, fekk {actual}',
    'positive-integer.not-positive' => 'Verdien ved "{path}" må vere eit positivt heiltal, fekk {value}',
    'negative-integer.not-negative' => 'Verdien ved "{path}" må vere eit negativt heiltal, fekk {value}',
    'range.below-min' => 'Verdien ved "{path}" ({actual}) er under minimum på {min}',
    'range.above-max' => 'Verdien ved "{path}" ({actual}) er over maksimum på {max}',
    'min.below-min' => 'Verdien ved "{path}" ({actual}) er under minimum på {min}',
    'max.above-max' => 'Verdien ved "{path}" ({actual}) er over maksimum på {max}',
    'not-empty.empty-value' => 'Verdien ved "{path}" kan ikkje vere tom',
    'in.not-in-list' => 'Verdien ved "{path}" ({value}) må vere ein av: {allowed}',
    'not-in.in-list' => 'Verdien ved "{path}" ({value}) kan ikkje vere nokon av: {disallowed}',
    'equal-to.not-equal' => 'Verdien ved "{path}" ({value}) må vere lik {expected}',
    'not-equal-to.equal' => 'Verdien ved "{path}" ({value}) kan ikkje vere lik {disallowed}',
    'date-time.invalid-format' => 'Verdien ved "{path}" er ikkje eit gyldig dato/tidspunkt',
    'duration.invalid-format' => 'Verdien ved "{path}" er ikkje ei gyldig ISO 8601-varigheit ({reason})',
    'time-zone.unknown' => 'Verdien ved "{path}" ({received}) er ikkje ei kjend tidssone',
    'time-zone-identifier.not-identifier' => 'Verdien ved "{path}" ({value}) er ikkje ei namngjeven tidssone',
    'time-zone-country.not-in-country' => 'Verdien ved "{path}" ({value}) er ikkje ei tidssone i {country}',
    'instant.not-before' => 'Verdien ved "{path}" må vere før {anchor}',
    'instant.not-after' => 'Verdien ved "{path}" må vere etter {anchor}',
    'period.inverted' => 'Perioden ved "{path}" er invertert: slutt ({end}) kjem før start ({start})',
    'uuid.invalid-format' => 'Verdien ved "{path}" er ikkje ein gyldig UUID',
    'uuid-v4.invalid-format' => 'Verdien ved "{path}" er ikkje ein gyldig UUID v4',
    'uuid-v7.invalid-format' => 'Verdien ved "{path}" er ikkje ein gyldig UUID v7',
    'ulid.invalid-format' => 'Verdien ved "{path}" er ikkje ein gyldig ULID',
    'ean.invalid-format' => 'Verdien ved "{path}" er ikkje ein gyldig EAN-strekkode',
    'ean.invalid-checksum' => 'Verdien ved "{path}" har ein ugyldig EAN-kontrollsum',
    'ip.invalid-format' => 'Verdien ved "{path}" er ikkje ei gyldig IP-adresse',
    'ipv4.invalid-format' => 'Verdien ved "{path}" er ikkje ei gyldig IPv4-adresse',
    'ipv6.invalid-format' => 'Verdien ved "{path}" er ikkje ei gyldig IPv6-adresse',
    'alpha.not-alpha' => 'Verdien ved "{path}" kan berre innehalde bokstavar',
    'alpha-numeric.not-alphanumeric' => 'Verdien ved "{path}" kan berre innehalde bokstavar og siffer',
    'not-suspicious.suspicious' => 'Verdien ved "{path}" inneheld teikn som kan forvekslast med andre',
    'iban.invalid-format' => 'Verdien ved "{path}" er ikkje ein gyldig IBAN',
    'iban.invalid-checksum' => 'Verdien ved "{path}" har ein ugyldig IBAN-kontrollsum',
    'credit-card.invalid-format' => 'Verdien ved "{path}" er ikkje eit gyldig kredittkortnummer',
    'credit-card.invalid-checksum' => 'Verdien ved "{path}" har ein ugyldig kredittkortkontrollsum',
    'country-code.not-recognized' => 'Verdien ved "{path}" ({value}) er ikkje ein kjend ISO 3166-1 alpha-2-landkode',
    'language-code.not-recognized' => 'Verdien ved "{path}" ({value}) er ikkje ein kjend ISO 639-1-språkkode',
    'currency-code.not-recognized' => 'Verdien ved "{path}" ({value}) er ikkje ei kjend ISO 4217-valutakode',
    'locale.invalid' => 'Verdien ved "{path}" ({value}) er ikkje ei gyldig språkkode',
    'supported-locale.not-supported' => 'Verdien ved "{path}" ({value}) er ikkje eit støtta språk',
    'enum.wrong-instance' => 'Verdien ved "{path}" må vere ein instans av {expected}, fekk {received}',
    'fallback' => 'Validering feila ved "{path}" ({code})',
];
