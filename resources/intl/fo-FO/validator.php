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
    'common.wrong-type' => 'Virðið á "{path}" skal vera {expected}, fekk {received}',
    'common.null-value' => 'Virðið á "{path}" má ikki vera null',
    'common.invalid-encoding' => 'Virðið á "{path}" er ikki gyldugur tekstur',
    'email.invalid-format' => 'Virðið á "{path}" er ikki ein gyldug teldupostadressa',
    'url.invalid-format' => 'Virðið á "{path}" er ikki ein gyldug URL',
    'url.disallowed-scheme' => 'URL\'urin á "{path}" brúkar protokollina "{scheme}", loyvdar protokollir eru: {allowed}',
    'regex.no-match' => 'Virðið á "{path}" samsvarar ikki við kravda mynstrið',
    'hostname.invalid-format' => 'Virðið á "{path}" er ikki eitt gyldugt vertsnavn',
    'json.invalid-format' => 'Virðið á "{path}" er ikki gyldugt JSON',
    'base64.invalid-format' => 'Virðið á "{path}" er ikki gyldugt base64',
    'base64url.invalid-format' => 'Virðið á "{path}" er ikki gyldugt base64url',
    'prefixed-with.missing-prefix' => 'Virðið á "{path}" skal byrja við "{prefix}"',
    'suffixed-with.missing-suffix' => 'Virðið á "{path}" skal enda við "{suffix}"',
    'contains.missing-substring' => 'Virðið á "{path}" skal innihalda "{needle}"',
    'length.too-short' => 'Virðið á "{path}" skal vera í minsta lagi {min} bytes langt, fekk {actual}',
    'length.too-long' => 'Virðið á "{path}" má í mesta lagi vera {max} bytes langt, fekk {actual}',
    'character-length.too-short' => 'Virðið á "{path}" skal vera í minsta lagi {min} tekn langt, fekk {actual}',
    'character-length.too-long' => 'Virðið á "{path}" má í mesta lagi vera {max} tekn langt, fekk {actual}',
    'positive-integer.not-positive' => 'Virðið á "{path}" skal vera eitt positivt heiltal, fekk {value}',
    'negative-integer.not-negative' => 'Virðið á "{path}" skal vera eitt negativt heiltal, fekk {value}',
    'range.below-min' => 'Virðið á "{path}" ({actual}) er minni enn minstamarkið {min}',
    'range.above-max' => 'Virðið á "{path}" ({actual}) er størri enn hámarkið {max}',
    'min.below-min' => 'Virðið á "{path}" ({actual}) er minni enn minstamarkið {min}',
    'max.above-max' => 'Virðið á "{path}" ({actual}) er størri enn hámarkið {max}',
    'not-empty.empty-value' => 'Virðið á "{path}" má ikki vera tómt',
    'in.not-in-list' => 'Virðið á "{path}" ({value}) skal vera eitt av: {allowed}',
    'not-in.in-list' => 'Virðið á "{path}" ({value}) má ikki vera nakað av: {disallowed}',
    'equal-to.not-equal' => 'Virðið á "{path}" ({value}) skal vera tað sama sum {expected}',
    'not-equal-to.equal' => 'Virðið á "{path}" ({value}) má ikki vera tað sama sum {disallowed}',
    'date-time.invalid-format' => 'Virðið á "{path}" er ikki ein gyldug dagfesting/tíð',
    'duration.invalid-format' => 'Virðið á "{path}" er ikki ein gyldug ISO 8601-tíðarlongd ({reason})',
    'time-zone.unknown' => 'Virðið á "{path}" ({received}) er ikki eitt kent tíðarbelti',
    'time-zone-identifier.not-identifier' => 'Virðið á "{path}" ({value}) er ikki eitt navngivið tíðarbelti',
    'time-zone-country.not-in-country' => 'Virðið á "{path}" ({value}) er ikki eitt tíðarbelti í {country}',
    'instant.not-before' => 'Virðið á "{path}" skal vera áðrenn {anchor}',
    'instant.not-after' => 'Virðið á "{path}" skal vera eftir {anchor}',
    'period.inverted' => 'Tíðarskeiðið á "{path}" er umvent: endin ({end}) er áðrenn byrjanin ({start})',
    'uuid.invalid-format' => 'Virðið á "{path}" er ikki eitt gyldugt UUID',
    'uuid-v4.invalid-format' => 'Virðið á "{path}" er ikki eitt gyldugt UUID v4',
    'uuid-v7.invalid-format' => 'Virðið á "{path}" er ikki eitt gyldugt UUID v7',
    'ulid.invalid-format' => 'Virðið á "{path}" er ikki eitt gyldugt ULID',
    'ean.invalid-format' => 'Virðið á "{path}" er ikki ein gyldug EAN-strikukoda',
    'ean.invalid-checksum' => 'Virðið á "{path}" hevur eitt ógyldugt EAN-kontrolltal',
    'ip.invalid-format' => 'Virðið á "{path}" er ikki ein gyldug IP-adressa',
    'ipv4.invalid-format' => 'Virðið á "{path}" er ikki ein gyldug IPv4-adressa',
    'ipv6.invalid-format' => 'Virðið á "{path}" er ikki ein gyldug IPv6-adressa',
    'alpha.not-alpha' => 'Virðið á "{path}" má bert innihalda bókstavir',
    'alpha-numeric.not-alphanumeric' => 'Virðið á "{path}" má bert innihalda bókstavir og tøl',
    'not-suspicious.suspicious' => 'Virðið á "{path}" inniheldur tekn, ið kunnu blandast saman við onnur',
    'iban.invalid-format' => 'Virðið á "{path}" er ikki eitt gyldugt IBAN',
    'iban.invalid-checksum' => 'Virðið á "{path}" hevur eitt ógyldugt IBAN-kontrolltal',
    'credit-card.invalid-format' => 'Virðið á "{path}" er ikki eitt gyldugt kreditkortnummar',
    'credit-card.invalid-checksum' => 'Virðið á "{path}" hevur eitt ógyldugt kreditkort-kontrolltal',
    'country-code.not-recognized' => 'Virðið á "{path}" ({value}) er ikki ein kend ISO 3166-1 alpha-2-landakoda',
    'language-code.not-recognized' => 'Virðið á "{path}" ({value}) er ikki ein kend ISO 639-1-málkoda',
    'currency-code.not-recognized' => 'Virðið á "{path}" ({value}) er ikki ein kend ISO 4217-gjaldoyrakoda',
    'locale.invalid' => 'Virðið á "{path}" ({value}) er ikki eitt gyldugt málkoda-merki',
    'supported-locale.not-supported' => 'Virðið á "{path}" ({value}) er ikki eitt stuðlað mál',
    'enum.wrong-instance' => 'Virðið á "{path}" skal vera eitt tilvik av {expected}, fekk {received}',
    'fallback' => 'Kanning miseydnaðist á "{path}" ({code})',
];
