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

namespace Tuxxedo\Validator\Message;

use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Alpha\AlphaViolationCode;
use Tuxxedo\Validator\Rule\AlphaNumeric\AlphaNumericViolationCode;
use Tuxxedo\Validator\Rule\Base64\Base64ViolationCode;
use Tuxxedo\Validator\Rule\Base64Url\Base64UrlViolationCode;
use Tuxxedo\Validator\Rule\CharacterLength\CharacterLengthViolationCode;
use Tuxxedo\Validator\Rule\Contains\ContainsViolationCode;
use Tuxxedo\Validator\Rule\CountryCode\CountryCodeViolationCode;
use Tuxxedo\Validator\Rule\CreditCard\CreditCardViolationCode;
use Tuxxedo\Validator\Rule\DateTime\DateTimeViolationCode;
use Tuxxedo\Validator\Rule\Ean\EanViolationCode;
use Tuxxedo\Validator\Rule\Email\EmailViolationCode;
use Tuxxedo\Validator\Rule\EqualTo\EqualToViolationCode;
use Tuxxedo\Validator\Rule\Hostname\HostnameViolationCode;
use Tuxxedo\Validator\Rule\Iban\IbanViolationCode;
use Tuxxedo\Validator\Rule\In\InViolationCode;
use Tuxxedo\Validator\Rule\Ip\IpViolationCode;
use Tuxxedo\Validator\Rule\Ipv4\Ipv4ViolationCode;
use Tuxxedo\Validator\Rule\Ipv6\Ipv6ViolationCode;
use Tuxxedo\Validator\Rule\Json\JsonViolationCode;
use Tuxxedo\Validator\Rule\LanguageCode\LanguageCodeViolationCode;
use Tuxxedo\Validator\Rule\Length\LengthViolationCode;
use Tuxxedo\Validator\Rule\Max\MaxViolationCode;
use Tuxxedo\Validator\Rule\Min\MinViolationCode;
use Tuxxedo\Validator\Rule\NegativeInteger\NegativeIntegerViolationCode;
use Tuxxedo\Validator\Rule\NotEmpty\NotEmptyViolationCode;
use Tuxxedo\Validator\Rule\NotEqualTo\NotEqualToViolationCode;
use Tuxxedo\Validator\Rule\NotIn\NotInViolationCode;
use Tuxxedo\Validator\Rule\PositiveInteger\PositiveIntegerViolationCode;
use Tuxxedo\Validator\Rule\PrefixedWith\PrefixedWithViolationCode;
use Tuxxedo\Validator\Rule\Range\RangeViolationCode;
use Tuxxedo\Validator\Rule\Regex\RegexViolationCode;
use Tuxxedo\Validator\Rule\SuffixedWith\SuffixedWithViolationCode;
use Tuxxedo\Validator\Rule\Url\UrlViolationCode;
use Tuxxedo\Validator\Rule\Uuid\UuidViolationCode;
use Tuxxedo\Validator\Rule\UuidV4\UuidV4ViolationCode;
use Tuxxedo\Validator\Rule\UuidV7\UuidV7ViolationCode;
use Tuxxedo\Validator\ViolationInterface;

class DefaultEnglishMessageFormatter implements MessageFormatterInterface
{
    /**
     * @var array<string, string>
     */
    private array $templates;

    /**
     * @param array<string, string> $templates
     */
    public function __construct(
        array $templates = [],
    ) {
        $this->templates = \array_merge(
            self::builtinTemplates(),
            $templates,
        );
    }

    public function format(
        ViolationInterface $violation,
    ): string {
        $template = $this->templates[$violation->code->value]
            ?? \sprintf(
                'Validation failed at "%s" (%s)',
                $violation->propertyPath,
                (string) $violation->code->value,
            );

        return $this->interpolate(
            template: $template,
            placeholders: $this->buildPlaceholders(
                violation: $violation,
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function builtinTemplates(): array
    {
        return [
            CommonViolationCode::WRONG_TYPE->value => 'Value at "{path}" must be {expected}, got {received}',
            CommonViolationCode::NULL_VALUE->value => 'Value at "{path}" may not be null',
            EmailViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid email address',
            UrlViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid URL',
            UrlViolationCode::DISALLOWED_SCHEME->value => 'URL at "{path}" uses scheme "{scheme}", allowed schemes are: {allowed}',
            RegexViolationCode::NO_MATCH->value => 'Value at "{path}" does not match the required pattern',
            HostnameViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid hostname',
            JsonViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not valid JSON',
            Base64ViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not valid base64',
            Base64UrlViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not valid base64url',
            PrefixedWithViolationCode::MISSING_PREFIX->value => 'Value at "{path}" must start with "{prefix}"',
            SuffixedWithViolationCode::MISSING_SUFFIX->value => 'Value at "{path}" must end with "{suffix}"',
            ContainsViolationCode::MISSING_SUBSTRING->value => 'Value at "{path}" must contain "{needle}"',
            LengthViolationCode::TOO_SHORT->value => 'Value at "{path}" must be at least {min} bytes long, got {actual}',
            LengthViolationCode::TOO_LONG->value => 'Value at "{path}" must be no more than {max} bytes long, got {actual}',
            CharacterLengthViolationCode::TOO_SHORT->value => 'Value at "{path}" must be at least {min} characters long, got {actual}',
            CharacterLengthViolationCode::TOO_LONG->value => 'Value at "{path}" must be no more than {max} characters long, got {actual}',
            PositiveIntegerViolationCode::NOT_POSITIVE->value => 'Value at "{path}" must be a positive integer, got {value}',
            NegativeIntegerViolationCode::NOT_NEGATIVE->value => 'Value at "{path}" must be a negative integer, got {value}',
            RangeViolationCode::BELOW_MIN->value => 'Value at "{path}" ({actual}) is below the minimum of {min}',
            RangeViolationCode::ABOVE_MAX->value => 'Value at "{path}" ({actual}) is above the maximum of {max}',
            MinViolationCode::BELOW_MIN->value => 'Value at "{path}" ({actual}) is below the minimum of {min}',
            MaxViolationCode::ABOVE_MAX->value => 'Value at "{path}" ({actual}) is above the maximum of {max}',
            NotEmptyViolationCode::EMPTY_VALUE->value => 'Value at "{path}" may not be empty',
            InViolationCode::NOT_IN_LIST->value => 'Value at "{path}" ({value}) must be one of: {allowed}',
            NotInViolationCode::IN_LIST->value => 'Value at "{path}" ({value}) may not be any of: {disallowed}',
            EqualToViolationCode::NOT_EQUAL->value => 'Value at "{path}" ({value}) must equal {expected}',
            NotEqualToViolationCode::EQUAL->value => 'Value at "{path}" ({value}) may not equal {disallowed}',
            DateTimeViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid date/time',
            UuidViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid UUID',
            UuidV4ViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid UUID v4',
            UuidV7ViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid UUID v7',
            EanViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid EAN barcode',
            EanViolationCode::INVALID_CHECKSUM->value => 'Value at "{path}" has an invalid EAN checksum',
            IpViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid IP address',
            Ipv4ViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid IPv4 address',
            Ipv6ViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid IPv6 address',
            AlphaViolationCode::NOT_ALPHA->value => 'Value at "{path}" must contain only letters',
            AlphaNumericViolationCode::NOT_ALPHANUMERIC->value => 'Value at "{path}" must contain only letters and digits',
            IbanViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid IBAN',
            IbanViolationCode::INVALID_CHECKSUM->value => 'Value at "{path}" has an invalid IBAN checksum',
            CreditCardViolationCode::INVALID_FORMAT->value => 'Value at "{path}" is not a valid credit card number',
            CreditCardViolationCode::INVALID_CHECKSUM->value => 'Value at "{path}" has an invalid credit card checksum',
            CountryCodeViolationCode::NOT_RECOGNIZED->value => 'Value at "{path}" ({value}) is not a recognized ISO 3166-1 alpha-2 country code',
            LanguageCodeViolationCode::NOT_RECOGNIZED->value => 'Value at "{path}" ({value}) is not a recognized ISO 639-1 language code',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildPlaceholders(
        ViolationInterface $violation,
    ): array {
        $placeholders = [
            'path' => $violation->propertyPath,
            'value' => self::stringifyValue(
                value: $violation->invalidValue,
            ),
        ];

        if ($violation->context !== null) {
            foreach (\get_object_vars($violation->context) as $key => $value) {
                $placeholders[$key] = self::stringifyValue(
                    value: $value,
                );
            }
        }

        return $placeholders;
    }

    private static function stringifyValue(
        mixed $value,
    ): string {
        if ($value === null) {
            return 'null';
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (\is_scalar($value)) {
            return (string) $value;
        }

        if (\is_array($value)) {
            return \implode(', ', \array_map(self::stringifyValue(...), $value));
        }

        return \get_debug_type($value);
    }

    /**
     * @param array<string, string> $placeholders
     */
    private function interpolate(
        string $template,
        array $placeholders,
    ): string {
        $search = [];
        $replace = [];

        foreach ($placeholders as $name => $value) {
            $search[] = '{' . $name . '}';
            $replace[] = $value;
        }

        return \str_replace($search, $replace, $template);
    }
}
