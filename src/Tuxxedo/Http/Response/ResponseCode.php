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

namespace Tuxxedo\Http\Response;

use Tuxxedo\Http\HttpException;

enum ResponseCode: int
{
    case CONTINUE = 100;
    case SWITCHING_PROTOCOLS = 101;
    case PROCESSING = 102;
    case EARLY_HINTS = 103;
    case UPLOAD_RESUMPTION_SUPPORTED = 104;
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NON_AUTHORITATIVE_INFORMATION = 203;
    case NO_CONTENT = 204;
    case RESET_CONTENT = 205;
    case PARTIAL_CONTENT = 206;
    case MULTI_STATUS = 207;
    case ALREADY_REPORTED = 208;
    case IM_USED = 226;
    case MULTIPLE_CHOICES = 300;
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case SEE_OTHER = 303;
    case NOT_MODIFIED = 304;
    case USE_PROXY = 305;
    case SWITCH_PROXY = 306;
    case TEMPORARY_REDIRECT = 307;
    case PERMANENT_REDIRECT = 308;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case PAYMENT_REQUIRED = 402;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case NOT_ACCEPTABLE = 406;
    case PROXY_AUTHENTICATION_REQUIRED = 407;
    case REQUEST_TIMEOUT = 408;
    case CONFLICT = 409;
    case GONE = 410;
    case LENGTH_REQUIRED = 411;
    case PRECONDITION_FAILED = 412;
    case CONTENT_TOO_LARGE = 413;
    case URI_TOO_LONG = 414;
    case UNSUPPORTED_MEDIA_TYPE = 415;
    case RANGE_NOT_SATISFIABLE = 416;
    case EXPECTATION_FAILED = 417;
    case MISDIRECTED_REQUEST = 421;
    case UNPROCESSABLE_CONTENT = 422;
    case LOCKED = 423;
    case FAILED_DEPENDENCY = 424;
    case TOO_EARLY = 425;
    case UPGRADE_REQUIRED = 426;
    case PRECONDITION_REQUIRED = 428;
    case TOO_MANY_REQUESTS = 429;
    case REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    case NO_RESPONSE = 444;
    case UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    case CLIENT_CLOSED_REQUEST = 499;
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;
    case HTTP_VERSION_NOT_SUPPORTED = 505;
    case VARIANT_ALSO_NEGOTIATES = 506;
    case INSUFFICIENT_STORAGE = 507;
    case LOOP_DETECTED = 508;
    case NOT_EXTENDED = 510;
    case NETWORK_AUTHENTICATION_REQUIRED = 511;
    case WEB_SERVER_RETURNED_UNKNOWN_ERROR = 520;
    case WEB_SERVER_IS_DOWN = 521;
    case CONNECTION_TIMED_OUT = 522;
    case ORIGIN_IS_UNREACHABLE = 523;
    case A_TIMEOUT_OCCURRED = 524;
    case SSL_HANDSHAKE_FAILED = 525;
    case INVALID_SSL_CERTIFICATE = 526;
    case RAILGUN_ERROR = 527;
    case NETWORK_READ_TIMEOUT_ERROR = 598;
    case NETWORK_CONNECT_TIMEOUT_ERROR = 599;

    /**
     * @throws HttpException
     */
    public static function resolve(
        int $statusCode,
    ): self {
        if ($statusCode < 100 || $statusCode > 599) {
            throw HttpException::fromInternalServerError();
        }

        return self::tryFrom($statusCode) ?? self::from(\intdiv($statusCode, 100) * 100);
    }

    public function getStatusCode(): int
    {
        return $this->value;
    }

    public function getStatusText(): string
    {
        return match ($this) {
            self::CONTINUE => 'Continue',
            self::SWITCHING_PROTOCOLS => 'Switching Protocols',
            self::PROCESSING => 'Processing',
            self::EARLY_HINTS => 'Early Hints',
            self::UPLOAD_RESUMPTION_SUPPORTED => 'Upload Resumption Supported',
            self::OK => 'OK',
            self::CREATED => 'Created',
            self::ACCEPTED => 'Accepted',
            self::NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
            self::NO_CONTENT => 'No Content',
            self::RESET_CONTENT => 'Reset Content',
            self::PARTIAL_CONTENT => 'Partial Content',
            self::MULTI_STATUS => 'Multi-Status',
            self::ALREADY_REPORTED => 'Already Reported',
            self::IM_USED => 'IM Used',
            self::MULTIPLE_CHOICES => 'Multiple Choices',
            self::MOVED_PERMANENTLY => 'Moved Permanently',
            self::FOUND => 'Found',
            self::SEE_OTHER => 'See Other',
            self::NOT_MODIFIED => 'Not Modified',
            self::USE_PROXY => 'Use Proxy',
            self::SWITCH_PROXY => 'Switch Proxy',
            self::TEMPORARY_REDIRECT => 'Temporary Redirect',
            self::PERMANENT_REDIRECT => 'Permanent Redirect',
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::PAYMENT_REQUIRED => 'Payment Required',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::NOT_ACCEPTABLE => 'Not Acceptable',
            self::PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
            self::REQUEST_TIMEOUT => 'Request Timeout',
            self::CONFLICT => 'Conflict',
            self::GONE => 'Gone',
            self::LENGTH_REQUIRED => 'Length Required',
            self::PRECONDITION_FAILED => 'Precondition Failed',
            self::CONTENT_TOO_LARGE => 'Content Too Large',
            self::URI_TOO_LONG => 'URI Too Long',
            self::UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
            self::RANGE_NOT_SATISFIABLE => 'Range Not Satisfiable',
            self::EXPECTATION_FAILED => 'Expectation Failed',
            self::MISDIRECTED_REQUEST => 'Misdirected Request',
            self::UNPROCESSABLE_CONTENT => 'Unprocessable Content',
            self::LOCKED => 'Locked',
            self::FAILED_DEPENDENCY => 'Failed Dependency',
            self::TOO_EARLY => 'Too Early',
            self::UPGRADE_REQUIRED => 'Upgrade Required',
            self::PRECONDITION_REQUIRED => 'Precondition Required',
            self::TOO_MANY_REQUESTS => 'Too Many Requests',
            self::REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
            self::NO_RESPONSE => 'No Response',
            self::UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
            self::CLIENT_CLOSED_REQUEST => 'Client Closed Request',
            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::NOT_IMPLEMENTED => 'Not Implemented',
            self::BAD_GATEWAY => 'Bad Gateway',
            self::SERVICE_UNAVAILABLE => 'Service Unavailable',
            self::GATEWAY_TIMEOUT => 'Gateway Timeout',
            self::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported',
            self::VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
            self::INSUFFICIENT_STORAGE => 'Insufficient Storage',
            self::LOOP_DETECTED => 'Loop Detected',
            self::NOT_EXTENDED => 'Not Extended',
            self::NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
            self::WEB_SERVER_RETURNED_UNKNOWN_ERROR => 'Web Server Returned an Unknown Error',
            self::WEB_SERVER_IS_DOWN => 'Web Server Is Down',
            self::CONNECTION_TIMED_OUT => 'Connection Timed Out',
            self::ORIGIN_IS_UNREACHABLE => 'Origin Is Unreachable',
            self::A_TIMEOUT_OCCURRED => 'A Timeout Occurred',
            self::SSL_HANDSHAKE_FAILED => 'SSL Handshake Failed',
            self::INVALID_SSL_CERTIFICATE => 'Invalid SSL Certificate',
            self::RAILGUN_ERROR => 'Railgun Error',
            self::NETWORK_READ_TIMEOUT_ERROR => 'Network Read Timeout Error',
            self::NETWORK_CONNECT_TIMEOUT_ERROR => 'Network Connect Timeout Error',
        };
    }
}
