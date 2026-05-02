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

namespace Unit\Http\Response;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Response\ResponseCode;

class ResponseCodeTest extends TestCase
{
    /**
     * @return \Generator<array{0: ResponseCode, 1: int, 2: string}>
     */
    public static function responseCodeDataProvider(): \Generator
    {
        yield [
            ResponseCode::CONTINUE,
            100,
            'Continue',
        ];

        yield [
            ResponseCode::SWITCHING_PROTOCOLS,
            101,
            'Switching Protocols',
        ];

        yield [
            ResponseCode::PROCESSING,
            102,
            'Processing',
        ];

        yield [
            ResponseCode::EARLY_HINTS,
            103,
            'Early Hints',
        ];

        yield [
            ResponseCode::OK,
            200,
            'OK',
        ];

        yield [
            ResponseCode::CREATED,
            201,
            'Created',
        ];

        yield [
            ResponseCode::ACCEPTED,
            202,
            'Accepted',
        ];

        yield [
            ResponseCode::NON_AUTHORITATIVE_INFORMATION,
            203,
            'Non-Authoritative Information',
        ];

        yield [
            ResponseCode::NO_CONTENT,
            204,
            'No Content',
        ];

        yield [
            ResponseCode::RESET_CONTENT,
            205,
            'Reset Content',
        ];

        yield [
            ResponseCode::PARTIAL_CONTENT,
            206,
            'Partial Content',
        ];

        yield [
            ResponseCode::MULTI_STATUS,
            207,
            'Multi-Status',
        ];

        yield [
            ResponseCode::ALREADY_REPORTED,
            208,
            'Already Reported',
        ];

        yield [
            ResponseCode::IM_USED,
            226,
            'IM Used',
        ];

        yield [
            ResponseCode::MULTIPLE_CHOICES,
            300,
            'Multiple Choices',
        ];

        yield [
            ResponseCode::MOVED_PERMANENTLY,
            301,
            'Moved Permanently',
        ];

        yield [
            ResponseCode::FOUND,
            302,
            'Found',
        ];

        yield [
            ResponseCode::SEE_OTHER,
            303,
            'See Other',
        ];

        yield [
            ResponseCode::NOT_MODIFIED,
            304,
            'Not Modified',
        ];

        yield [
            ResponseCode::USE_PROXY,
            305,
            'Use Proxy',
        ];

        yield [
            ResponseCode::SWITCH_PROXY,
            306,
            'Switch Proxy',
        ];

        yield [
            ResponseCode::TEMPORARY_REDIRECT,
            307,
            'Temporary Redirect',
        ];

        yield [
            ResponseCode::PERMANENT_REDIRECT,
            308,
            'Permanent Redirect',
        ];

        yield [
            ResponseCode::BAD_REQUEST,
            400,
            'Bad Request',
        ];

        yield [
            ResponseCode::UNAUTHORIZED,
            401,
            'Unauthorized',
        ];

        yield [
            ResponseCode::PAYMENT_REQUIRED,
            402,
            'Payment Required',
        ];

        yield [
            ResponseCode::FORBIDDEN,
            403,
            'Forbidden',
        ];

        yield [
            ResponseCode::NOT_FOUND,
            404,
            'Not Found',
        ];

        yield [
            ResponseCode::METHOD_NOT_ALLOWED,
            405,
            'Method Not Allowed',
        ];

        yield [
            ResponseCode::NOT_ACCEPTABLE,
            406,
            'Not Acceptable',
        ];

        yield [
            ResponseCode::PROXY_AUTHENTICATION_REQUIRED,
            407,
            'Proxy Authentication Required',
        ];

        yield [
            ResponseCode::REQUEST_TIMEOUT,
            408,
            'Request Timeout',
        ];

        yield [
            ResponseCode::CONFLICT,
            409,
            'Conflict',
        ];

        yield [
            ResponseCode::GONE,
            410,
            'Gone',
        ];

        yield [
            ResponseCode::LENGTH_REQUIRED,
            411,
            'Length Required',
        ];

        yield [
            ResponseCode::PRECONDITION_FAILED,
            412,
            'Precondition Failed',
        ];

        yield [
            ResponseCode::PAYLOAD_TOO_LARGE,
            413,
            'Payload Too Large',
        ];

        yield [
            ResponseCode::URI_TOO_LONG,
            414,
            'URI Too Long',
        ];

        yield [
            ResponseCode::UNSUPPORTED_MEDIA_TYPE,
            415,
            'Unsupported Media Type',
        ];

        yield [
            ResponseCode::RANGE_NOT_SATISFIABLE,
            416,
            'Range Not Satisfiable',
        ];

        yield [
            ResponseCode::EXPECTATION_FAILED,
            417,
            'Expectation Failed',
        ];

        yield [
            ResponseCode::MISDIRECTED_REQUEST,
            421,
            'Misdirected Request',
        ];

        yield [
            ResponseCode::UNPROCESSABLE_ENTITY,
            422,
            'Unprocessable Entity',
        ];

        yield [
            ResponseCode::LOCKED,
            423,
            'Locked',
        ];

        yield [
            ResponseCode::FAILED_DEPENDENCY,
            424,
            'Failed Dependency',
        ];

        yield [
            ResponseCode::TOO_EARLY,
            425,
            'Too Early',
        ];

        yield [
            ResponseCode::UPGRADE_REQUIRED,
            426,
            'Upgrade Required',
        ];

        yield [
            ResponseCode::PRECONDITION_REQUIRED,
            428,
            'Precondition Required',
        ];

        yield [
            ResponseCode::TOO_MANY_REQUESTS,
            429,
            'Too Many Requests',
        ];

        yield [
            ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE,
            431,
            'Request Header Fields Too Large',
        ];

        yield [
            ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS,
            451,
            'Unavailable For Legal Reasons',
        ];

        yield [
            ResponseCode::INTERNAL_SERVER_ERROR,
            500,
            'Internal Server Error',
        ];

        yield [
            ResponseCode::NOT_IMPLEMENTED,
            501,
            'Not Implemented',
        ];

        yield [
            ResponseCode::BAD_GATEWAY,
            502,
            'Bad Gateway',
        ];

        yield [
            ResponseCode::SERVICE_UNAVAILABLE,
            503,
            'Service Unavailable',
        ];

        yield [
            ResponseCode::GATEWAY_TIMEOUT,
            504,
            'Gateway Timeout',
        ];

        yield [
            ResponseCode::HTTP_VERSION_NOT_SUPPORTED,
            505,
            'HTTP Version Not Supported',
        ];

        yield [
            ResponseCode::VARIANT_ALSO_NEGOTIATES,
            506,
            'Variant Also Negotiates',
        ];

        yield [
            ResponseCode::INSUFFICIENT_STORAGE,
            507,
            'Insufficient Storage',
        ];

        yield [
            ResponseCode::LOOP_DETECTED,
            508,
            'Loop Detected',
        ];

        yield [
            ResponseCode::NOT_EXTENDED,
            510,
            'Not Extended',
        ];

        yield [
            ResponseCode::NETWORK_AUTHENTICATION_REQUIRED,
            511,
            'Network Authentication Required',
        ];
    }

    #[DataProvider('responseCodeDataProvider')]
    public function testStatusCodeAndText(
        ResponseCode $code,
        int $expectedStatusCode,
        string $expectedStatusText,
    ): void {
        self::assertSame($expectedStatusCode, $code->getStatusCode());
        self::assertSame($expectedStatusText, $code->getStatusText());
    }
}
