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
    'common.wrong-type' => 'Arvon kohdassa "{path}" on oltava {expected}, saatiin {received}',
    'common.null-value' => 'Arvo kohdassa "{path}" ei saa olla null',
    'email.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen sähköpostiosoite',
    'url.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen URL',
    'url.disallowed-scheme' => 'URL kohdassa "{path}" käyttää skeemaa "{scheme}", sallitut skeemat ovat: {allowed}',
    'regex.no-match' => 'Arvo kohdassa "{path}" ei vastaa vaadittua kaavaa',
    'hostname.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen isäntänimi',
    'json.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollista JSONia',
    'base64.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollista base64-dataa',
    'base64url.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollista base64url-dataa',
    'prefixed-with.missing-prefix' => 'Arvon kohdassa "{path}" on alettava merkkijonolla "{prefix}"',
    'suffixed-with.missing-suffix' => 'Arvon kohdassa "{path}" on päätyttävä merkkijonoon "{suffix}"',
    'contains.missing-substring' => 'Arvon kohdassa "{path}" on sisällettävä "{needle}"',
    'length.too-short' => 'Arvon kohdassa "{path}" on oltava vähintään {min} tavua pitkä, saatiin {actual}',
    'length.too-long' => 'Arvo kohdassa "{path}" saa olla enintään {max} tavua pitkä, saatiin {actual}',
    'character-length.too-short' => 'Arvon kohdassa "{path}" on oltava vähintään {min} merkkiä pitkä, saatiin {actual}',
    'character-length.too-long' => 'Arvo kohdassa "{path}" saa olla enintään {max} merkkiä pitkä, saatiin {actual}',
    'positive-integer.not-positive' => 'Arvon kohdassa "{path}" on oltava positiivinen kokonaisluku, saatiin {value}',
    'negative-integer.not-negative' => 'Arvon kohdassa "{path}" on oltava negatiivinen kokonaisluku, saatiin {value}',
    'range.below-min' => 'Arvo kohdassa "{path}" ({actual}) on alle minimin {min}',
    'range.above-max' => 'Arvo kohdassa "{path}" ({actual}) ylittää maksimin {max}',
    'min.below-min' => 'Arvo kohdassa "{path}" ({actual}) on alle minimin {min}',
    'max.above-max' => 'Arvo kohdassa "{path}" ({actual}) ylittää maksimin {max}',
    'not-empty.empty-value' => 'Arvo kohdassa "{path}" ei saa olla tyhjä',
    'in.not-in-list' => 'Arvon kohdassa "{path}" ({value}) on oltava jokin seuraavista: {allowed}',
    'not-in.in-list' => 'Arvo kohdassa "{path}" ({value}) ei saa olla mikään seuraavista: {disallowed}',
    'equal-to.not-equal' => 'Arvon kohdassa "{path}" ({value}) on oltava {expected}',
    'not-equal-to.equal' => 'Arvo kohdassa "{path}" ({value}) ei saa olla {disallowed}',
    'date-time.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen päivämäärä/aika',
    'duration.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen ISO 8601 -kesto ({reason})',
    'time-zone.unknown' => 'Arvo kohdassa "{path}" ({received}) ei ole tunnettu aikavyöhyke',
    'instant.not-before' => 'Arvon kohdassa "{path}" on oltava aiemmin kuin {anchor}',
    'instant.not-after' => 'Arvon kohdassa "{path}" on oltava myöhemmin kuin {anchor}',
    'period.inverted' => 'Jakso kohdassa "{path}" on käänteinen: loppu ({end}) on ennen alkua ({start})',
    'uuid.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen UUID',
    'uuid-v4.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen UUID v4',
    'uuid-v7.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen UUID v7',
    'ulid.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen ULID',
    'ean.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen EAN-viivakoodi',
    'ean.invalid-checksum' => 'Arvolla kohdassa "{path}" on virheellinen EAN-tarkistussumma',
    'ip.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen IP-osoite',
    'ipv4.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen IPv4-osoite',
    'ipv6.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen IPv6-osoite',
    'alpha.not-alpha' => 'Arvo kohdassa "{path}" saa sisältää vain kirjaimia',
    'alpha-numeric.not-alphanumeric' => 'Arvo kohdassa "{path}" saa sisältää vain kirjaimia ja numeroita',
    'iban.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen IBAN',
    'iban.invalid-checksum' => 'Arvolla kohdassa "{path}" on virheellinen IBAN-tarkistussumma',
    'credit-card.invalid-format' => 'Arvo kohdassa "{path}" ei ole kelvollinen luottokortin numero',
    'credit-card.invalid-checksum' => 'Arvolla kohdassa "{path}" on virheellinen luottokortin tarkistussumma',
    'country-code.not-recognized' => 'Arvo kohdassa "{path}" ({value}) ei ole tunnettu ISO 3166-1 alpha-2 -maakoodi',
    'language-code.not-recognized' => 'Arvo kohdassa "{path}" ({value}) ei ole tunnettu ISO 639-1 -kielikoodi',
    'enum.wrong-instance' => 'Arvon kohdassa "{path}" on oltava {expected}-instanssi, saatiin {received}',
    'fallback' => 'Validointi epäonnistui kohdassa "{path}" ({code})',
];
