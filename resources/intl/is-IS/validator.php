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
    'common.wrong-type' => 'Gildið á "{path}" verður að vera {expected}, fékk {received}',
    'common.null-value' => 'Gildið á "{path}" má ekki vera null',
    'common.invalid-encoding' => 'Gildið á "{path}" er ekki gildur texti',
    'email.invalid-format' => 'Gildið á "{path}" er ekki gilt netfang',
    'url.invalid-format' => 'Gildið á "{path}" er ekki gild vefslóð',
    'url.disallowed-scheme' => 'Vefslóðin á "{path}" notar skemað "{scheme}", leyfð skemu eru: {allowed}',
    'regex.no-match' => 'Gildið á "{path}" passar ekki við tilskilið mynstur',
    'hostname.invalid-format' => 'Gildið á "{path}" er ekki gilt hýsilnafn',
    'json.invalid-format' => 'Gildið á "{path}" er ekki gilt JSON',
    'base64.invalid-format' => 'Gildið á "{path}" er ekki gilt base64',
    'base64url.invalid-format' => 'Gildið á "{path}" er ekki gilt base64url',
    'prefixed-with.missing-prefix' => 'Gildið á "{path}" verður að byrja á "{prefix}"',
    'suffixed-with.missing-suffix' => 'Gildið á "{path}" verður að enda á "{suffix}"',
    'contains.missing-substring' => 'Gildið á "{path}" verður að innihalda "{needle}"',
    'length.too-short' => 'Gildið á "{path}" verður að vera að minnsta kosti {min} bæti að lengd, fékk {actual}',
    'length.too-long' => 'Gildið á "{path}" má mest vera {max} bæti að lengd, fékk {actual}',
    'character-length.too-short' => 'Gildið á "{path}" verður að vera að minnsta kosti {min} stafir að lengd, fékk {actual}',
    'character-length.too-long' => 'Gildið á "{path}" má mest vera {max} stafir að lengd, fékk {actual}',
    'positive-integer.not-positive' => 'Gildið á "{path}" verður að vera jákvæð heiltala, fékk {value}',
    'negative-integer.not-negative' => 'Gildið á "{path}" verður að vera neikvæð heiltala, fékk {value}',
    'range.below-min' => 'Gildið á "{path}" ({actual}) er undir lágmarkinu {min}',
    'range.above-max' => 'Gildið á "{path}" ({actual}) er yfir hámarkinu {max}',
    'min.below-min' => 'Gildið á "{path}" ({actual}) er undir lágmarkinu {min}',
    'max.above-max' => 'Gildið á "{path}" ({actual}) er yfir hámarkinu {max}',
    'not-empty.empty-value' => 'Gildið á "{path}" má ekki vera tómt',
    'in.not-in-list' => 'Gildið á "{path}" ({value}) verður að vera eitt af: {allowed}',
    'not-in.in-list' => 'Gildið á "{path}" ({value}) má ekki vera neitt af: {disallowed}',
    'equal-to.not-equal' => 'Gildið á "{path}" ({value}) verður að vera jafnt {expected}',
    'not-equal-to.equal' => 'Gildið á "{path}" ({value}) má ekki vera jafnt {disallowed}',
    'date-time.invalid-format' => 'Gildið á "{path}" er ekki gild dagsetning/tími',
    'duration.invalid-format' => 'Gildið á "{path}" er ekki gilt ISO 8601-tímabil ({reason})',
    'time-zone.unknown' => 'Gildið á "{path}" ({received}) er ekki þekkt tímabelti',
    'time-zone-identifier.not-identifier' => 'Gildið á "{path}" ({value}) er ekki nefnt tímabelti',
    'time-zone-country.not-in-country' => 'Gildið á "{path}" ({value}) er ekki tímabelti í {country}',
    'instant.not-before' => 'Gildið á "{path}" verður að vera fyrir {anchor}',
    'instant.not-after' => 'Gildið á "{path}" verður að vera eftir {anchor}',
    'period.inverted' => 'Tímabilið á "{path}" er öfugt: lok ({end}) eru á undan upphafi ({start})',
    'uuid.invalid-format' => 'Gildið á "{path}" er ekki gilt UUID',
    'uuid-v4.invalid-format' => 'Gildið á "{path}" er ekki gilt UUID v4',
    'uuid-v7.invalid-format' => 'Gildið á "{path}" er ekki gilt UUID v7',
    'ulid.invalid-format' => 'Gildið á "{path}" er ekki gilt ULID',
    'ean.invalid-format' => 'Gildið á "{path}" er ekki gilt EAN-strikamerki',
    'ean.invalid-checksum' => 'Gildið á "{path}" hefur ógilda EAN-vartölu',
    'ip.invalid-format' => 'Gildið á "{path}" er ekki gild IP-tala',
    'ipv4.invalid-format' => 'Gildið á "{path}" er ekki gild IPv4-tala',
    'ipv6.invalid-format' => 'Gildið á "{path}" er ekki gild IPv6-tala',
    'alpha.not-alpha' => 'Gildið á "{path}" má aðeins innihalda bókstafi',
    'alpha-numeric.not-alphanumeric' => 'Gildið á "{path}" má aðeins innihalda bókstafi og tölustafi',
    'not-suspicious.suspicious' => 'Gildið á "{path}" inniheldur stafi sem auðvelt er að rugla saman við aðra',
    'iban.invalid-format' => 'Gildið á "{path}" er ekki gilt IBAN',
    'iban.invalid-checksum' => 'Gildið á "{path}" hefur ógilda IBAN-vartölu',
    'credit-card.invalid-format' => 'Gildið á "{path}" er ekki gilt kreditkortanúmer',
    'credit-card.invalid-checksum' => 'Gildið á "{path}" hefur ógilda kreditkortavartölu',
    'country-code.not-recognized' => 'Gildið á "{path}" ({value}) er ekki þekktur ISO 3166-1 alpha-2-landskóði',
    'language-code.not-recognized' => 'Gildið á "{path}" ({value}) er ekki þekktur ISO 639-1-tungumálakóði',
    'currency-code.not-recognized' => 'Gildið á "{path}" ({value}) er ekki þekktur ISO 4217-gjaldmiðilskóði',
    'locale.invalid' => 'Gildið á "{path}" ({value}) er ekki gilt tungumálsauðkenni',
    'supported-locale.not-supported' => 'Gildið á "{path}" ({value}) er ekki stutt tungumál',
    'enum.wrong-instance' => 'Gildið á "{path}" verður að vera tilvik af {expected}, fékk {received}',
    'fallback' => 'Staðfesting mistókst á "{path}" ({code})',
];
