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
    'common.wrong-type' => 'Verdien ved "{path}" må være {expected}, fikk {received}',
    'common.null-value' => 'Verdien ved "{path}" kan ikke være null',
    'common.invalid-encoding' => 'Verdien ved "{path}" er ikke gyldig tekst',
    'email.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig e-postadresse',
    'url.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig URL',
    'url.disallowed-scheme' => 'URL-en ved "{path}" bruker skjemaet "{scheme}", tillatte skjemaer er: {allowed}',
    'regex.no-match' => 'Verdien ved "{path}" samsvarer ikke med det påkrevde mønsteret',
    'hostname.invalid-format' => 'Verdien ved "{path}" er ikke et gyldig vertsnavn',
    'json.invalid-format' => 'Verdien ved "{path}" er ikke gyldig JSON',
    'base64.invalid-format' => 'Verdien ved "{path}" er ikke gyldig base64',
    'base64url.invalid-format' => 'Verdien ved "{path}" er ikke gyldig base64url',
    'prefixed-with.missing-prefix' => 'Verdien ved "{path}" må starte med "{prefix}"',
    'suffixed-with.missing-suffix' => 'Verdien ved "{path}" må slutte med "{suffix}"',
    'contains.missing-substring' => 'Verdien ved "{path}" må inneholde "{needle}"',
    'length.too-short' => 'Verdien ved "{path}" må være minst {min} byte lang, fikk {actual}',
    'length.too-long' => 'Verdien ved "{path}" kan være høyst {max} byte lang, fikk {actual}',
    'character-length.too-short' => 'Verdien ved "{path}" må være minst {min} tegn lang, fikk {actual}',
    'character-length.too-long' => 'Verdien ved "{path}" kan være høyst {max} tegn lang, fikk {actual}',
    'positive-integer.not-positive' => 'Verdien ved "{path}" må være et positivt heltall, fikk {value}',
    'negative-integer.not-negative' => 'Verdien ved "{path}" må være et negativt heltall, fikk {value}',
    'range.below-min' => 'Verdien ved "{path}" ({actual}) er under minimum på {min}',
    'range.above-max' => 'Verdien ved "{path}" ({actual}) er over maksimum på {max}',
    'min.below-min' => 'Verdien ved "{path}" ({actual}) er under minimum på {min}',
    'max.above-max' => 'Verdien ved "{path}" ({actual}) er over maksimum på {max}',
    'not-empty.empty-value' => 'Verdien ved "{path}" kan ikke være tom',
    'in.not-in-list' => 'Verdien ved "{path}" ({value}) må være en av: {allowed}',
    'not-in.in-list' => 'Verdien ved "{path}" ({value}) kan ikke være noen av: {disallowed}',
    'equal-to.not-equal' => 'Verdien ved "{path}" ({value}) må være lik {expected}',
    'not-equal-to.equal' => 'Verdien ved "{path}" ({value}) kan ikke være lik {disallowed}',
    'date-time.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig dato/tid',
    'duration.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig ISO 8601-varighet ({reason})',
    'time-zone.unknown' => 'Verdien ved "{path}" ({received}) er ikke en kjent tidssone',
    'time-zone-identifier.not-identifier' => 'Verdien ved "{path}" ({value}) er ikke en navngitt tidssone',
    'time-zone-country.not-in-country' => 'Verdien ved "{path}" ({value}) er ikke en tidssone i {country}',
    'instant.not-before' => 'Verdien ved "{path}" må være før {anchor}',
    'instant.not-after' => 'Verdien ved "{path}" må være etter {anchor}',
    'period.inverted' => 'Perioden ved "{path}" er invertert: slutt ({end}) kommer før start ({start})',
    'uuid.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig UUID',
    'uuid-v4.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig UUID v4',
    'uuid-v7.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig UUID v7',
    'ulid.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig ULID',
    'ean.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig EAN-strekkode',
    'ean.invalid-checksum' => 'Verdien ved "{path}" har en ugyldig EAN-kontrollsum',
    'ip.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig IP-adresse',
    'ipv4.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig IPv4-adresse',
    'ipv6.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig IPv6-adresse',
    'alpha.not-alpha' => 'Verdien ved "{path}" kan bare inneholde bokstaver',
    'alpha-numeric.not-alphanumeric' => 'Verdien ved "{path}" kan bare inneholde bokstaver og sifre',
    'not-suspicious.suspicious' => 'Verdien ved "{path}" inneholder tegn som kan forveksles med andre',
    'iban.invalid-format' => 'Verdien ved "{path}" er ikke en gyldig IBAN',
    'iban.invalid-checksum' => 'Verdien ved "{path}" har en ugyldig IBAN-kontrollsum',
    'credit-card.invalid-format' => 'Verdien ved "{path}" er ikke et gyldig kredittkortnummer',
    'credit-card.invalid-checksum' => 'Verdien ved "{path}" har en ugyldig kredittkortkontrollsum',
    'country-code.not-recognized' => 'Verdien ved "{path}" ({value}) er ikke en kjent ISO 3166-1 alpha-2-landkode',
    'language-code.not-recognized' => 'Verdien ved "{path}" ({value}) er ikke en kjent ISO 639-1-språkkode',
    'currency-code.not-recognized' => 'Verdien ved "{path}" ({value}) er ikke en kjent ISO 4217-valutakode',
    'locale.invalid' => 'Verdien ved "{path}" ({value}) er ikke en gyldig språkkode',
    'supported-locale.not-supported' => 'Verdien ved "{path}" ({value}) er ikke et støttet språk',
    'enum.wrong-instance' => 'Verdien ved "{path}" må være en instans av {expected}, fikk {received}',
    'fallback' => 'Validering feilet ved "{path}" ({code})',
];
