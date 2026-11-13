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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Exception;
use Tuxxedo\Http\Response\PrefersResponseCodeInterface;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Validator\ValidationException;

class HttpClientException extends Exception implements PrefersResponseCodeInterface
{
    public ?ResponseCode $responseCode {
        get {
            return match ($this->failure) {
                HttpClientFailure::TIMEOUT => ResponseCode::GATEWAY_TIMEOUT,
                HttpClientFailure::AUTH => $this->response !== null
                    ? ResponseCode::BAD_GATEWAY
                    : ResponseCode::INTERNAL_SERVER_ERROR,
                HttpClientFailure::RULE, HttpClientFailure::REPLAY, HttpClientFailure::REQUEST, HttpClientFailure::ABORTED => ResponseCode::INTERNAL_SERVER_ERROR,
                default => ResponseCode::BAD_GATEWAY,
            };
        }
    }

    final public function __construct(
        string $message,
        public readonly HttpClientFailure $failure,
        public readonly ?ClientRequestInterface $request = null,
        public readonly ?ClientResponseInterface $response = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            previous: $previous,
        );
    }

    public static function fromUnexpectedStatus(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
    ): self {
        return new self(
            message: \sprintf(
                '%s responded with %d',
                self::formatTarget($request),
                $response->statusCode,
            ),
            failure: HttpClientFailure::STATUS,
            request: $request,
            response: $response,
        );
    }

    public static function fromEmptyBody(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
    ): self {
        return new self(
            message: \sprintf(
                '%s responded with %d and an empty body',
                self::formatTarget($request),
                $response->statusCode,
            ),
            failure: HttpClientFailure::BODY,
            request: $request,
            response: $response,
        );
    }

    public static function fromMalformedBody(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
        \Throwable $previous,
    ): self {
        return new self(
            message: \sprintf(
                '%s returned a malformed body: %s',
                self::formatTarget($request),
                $previous->getMessage(),
            ),
            failure: HttpClientFailure::BODY,
            request: $request,
            response: $response,
            previous: $previous,
        );
    }

    public static function fromInvalidBody(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
        ValidationException $previous,
    ): self {
        return new self(
            message: \sprintf(
                '%s returned a body that does not match the expected shape',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::BODY,
            request: $request,
            response: $response,
            previous: $previous,
        );
    }

    public static function fromUnsupportedContentType(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
    ): self {
        return new self(
            message: \sprintf(
                '%s returned the unsupported content type %s',
                self::formatTarget($request),
                self::formatQuoted(
                    $response->headers->has('Content-Type')
                        ? $response->headers->string('Content-Type')
                        : '',
                ),
            ),
            failure: HttpClientFailure::BODY,
            request: $request,
            response: $response,
        );
    }

    public static function fromTransportError(
        ClientRequestInterface $request,
        int $errorCode,
        string $error,
    ): self {
        return new self(
            message: \sprintf(
                '%s failed: %s (curl error %d)',
                self::formatTarget($request),
                $error,
                $errorCode,
            ),
            failure: HttpClientFailure::TRANSPORT,
            request: $request,
        );
    }

    public static function fromTimeout(
        ClientRequestInterface $request,
        HttpClientTimeout $timeout,
    ): self {
        return new self(
            message: \sprintf(
                '%s hit the %s timeout',
                self::formatTarget($request),
                \strtolower($timeout->name),
            ),
            failure: HttpClientFailure::TIMEOUT,
            request: $request,
        );
    }

    public static function fromMissingStatusLine(
        ClientRequestInterface $request,
    ): self {
        return new self(
            message: \sprintf(
                '%s returned no status line',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::PROTOCOL,
            request: $request,
        );
    }

    public static function fromMalformedResponseHead(
        ClientRequestInterface $request,
    ): self {
        return new self(
            message: \sprintf(
                '%s returned a status line or header that could not be parsed',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::PROTOCOL,
            request: $request,
        );
    }

    public static function fromMissingTransportResult(
        ClientRequestInterface $request,
    ): self {
        return new self(
            message: \sprintf(
                '%s got no result from the transport',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::PROTOCOL,
            request: $request,
        );
    }

    public static function fromTooManyRedirects(
        ClientRequestInterface $request,
        int $maximum,
    ): self {
        return new self(
            message: \sprintf(
                '%s redirected more than %d times',
                self::formatTarget($request),
                $maximum,
            ),
            failure: HttpClientFailure::REDIRECT,
            request: $request,
        );
    }

    public static function fromResponseTooLarge(
        ClientRequestInterface $request,
        int $limit,
    ): self {
        return new self(
            message: \sprintf(
                '%s returned a response larger than %d bytes',
                self::formatTarget($request),
                $limit,
            ),
            failure: HttpClientFailure::SIZE,
            request: $request,
        );
    }

    public static function fromExhaustedSequence(
        ClientRequestInterface $request,
        string $rule,
    ): self {
        return new self(
            message: \sprintf(
                '%s matched the rule %s, but its response sequence is exhausted',
                self::formatTarget($request),
                self::formatQuoted($rule),
            ),
            failure: HttpClientFailure::RULE,
            request: $request,
        );
    }

    public static function fromPrivateNetwork(
        ClientRequestInterface $request,
        string $address,
    ): self {
        return new self(
            message: \sprintf(
                '%s was denied because %s is a private network address',
                self::formatTarget($request),
                self::formatQuoted($address),
            ),
            failure: HttpClientFailure::RULE,
            request: $request,
        );
    }

    public static function fromUnmatchedRequest(
        ClientRequestInterface $request,
    ): self {
        return new self(
            message: \sprintf(
                '%s matched no rule and unmatched requests are denied',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::RULE,
            request: $request,
        );
    }

    public static function fromDeniedByRule(
        ClientRequestInterface $request,
        string $rule,
    ): self {
        return new self(
            message: \sprintf(
                '%s was denied by the rule %s',
                self::formatTarget($request),
                self::formatQuoted($rule),
            ),
            failure: HttpClientFailure::RULE,
            request: $request,
        );
    }

    public static function fromUnrewindableBody(
        ClientRequestInterface $request,
    ): self {
        return new self(
            message: \sprintf(
                '%s needs its body sent again, but the body is a stream that can only be read once',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::REPLAY,
            request: $request,
        );
    }

    public static function fromUnwritableDownload(
        ClientRequestInterface $request,
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                '%s could not be downloaded to %s',
                self::formatTarget($request),
                self::formatQuoted($path),
            ),
            failure: HttpClientFailure::REQUEST,
            request: $request,
        );
    }

    public static function fromForeignOrigin(
        ClientRequestInterface $request,
    ): self {
        return new self(
            message: \sprintf(
                '%s is outside the base URL of a strict client',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::REQUEST,
            request: $request,
        );
    }

    public static function fromAborted(
        ClientRequestInterface $request,
    ): self {
        return new self(
            message: \sprintf(
                '%s was aborted by its progress callback',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::ABORTED,
            request: $request,
        );
    }

    public static function fromDotSegments(
        ClientRequestInterface $request,
    ): self {
        return new self(
            message: \sprintf(
                '%s contains dot segments, which are not resolved against the base URL',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::REQUEST,
            request: $request,
        );
    }

    public static function fromMissingToken(
        ClientRequestInterface $request,
    ): self {
        return new self(
            message: \sprintf(
                '%s needs an access token, but the token store has no usable token',
                self::formatTarget($request),
            ),
            failure: HttpClientFailure::AUTH,
            request: $request,
        );
    }

    public static function fromTokenRequestFailed(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: \sprintf(
                '%s did not return a usable access token (status %d)',
                self::formatTarget($request),
                $response->statusCode,
            ),
            failure: HttpClientFailure::AUTH,
            request: $request,
            response: $response,
            previous: $previous,
        );
    }

    private static function formatTarget(
        ClientRequestInterface $request,
    ): string {
        $url = $request->url;

        if ($url->host === '') {
            return $request->method->name . ' ' . $url->path;
        }

        return \sprintf(
            '%s %s://%s%s%s',
            $request->method->name,
            $url->scheme,
            $url->host,
            $url->port !== null
                ? ':' . $url->port
                : '',
            $url->path,
        );
    }
}
