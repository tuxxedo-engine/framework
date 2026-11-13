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

namespace Tuxxedo\Security\Jwt;

use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Url\UrlException;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Security\Crypto\CryptoException;
use Tuxxedo\Security\Jwt\Key\KeySet;
use Tuxxedo\Security\Jwt\Key\KeySetInterface;

class JwksParser
{
    /**
     * @throws CryptoException
     * @throws HttpClientException
     * @throws JwtException
     * @throws UrlException
     */
    public static function parseUrl(
        HttpClientInterface $httpClient,
        UrlInterface|string $url,
    ): KeySetInterface {
        $request = new ClientRequest(
            method: Method::GET,
            url: $url,
            headers: [
                new Header('Accept', 'application/jwk-set+json, application/json'),
            ],
        );

        $response = $httpClient->send($request);

        if ($response->statusCode < 200 || $response->statusCode > 299) {
            throw HttpClientException::fromUnexpectedStatus($request, $response);
        }

        return self::parse($response->body->getRaw());
    }

    /**
     * @throws CryptoException
     * @throws JwtException
     */
    public static function parse(
        string $json,
    ): KeySetInterface {
        try {
            $decoded = \json_decode(
                json: $json,
                associative: true,
                flags: \JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw JwtException::fromJsonDecodeFailure(
                previous: $e,
            );
        }

        if (!\is_array($decoded)) {
            throw JwtException::fromMalformedJwks(
                reason: 'top-level value is not a JSON object',
            );
        }

        if ($decoded !== [] && \array_is_list($decoded)) {
            throw JwtException::fromMalformedJwks(
                reason: 'top-level value is a JSON array, not an object',
            );
        }

        if (!\array_key_exists('keys', $decoded)) {
            throw JwtException::fromMalformedJwks(
                reason: 'missing required "keys" field',
            );
        }

        $keys = $decoded['keys'];

        if (!\is_array($keys)) {
            throw JwtException::fromMalformedJwks(
                reason: '"keys" field is not a JSON array',
            );
        }

        if ($keys !== [] && !\array_is_list($keys)) {
            throw JwtException::fromMalformedJwks(
                reason: '"keys" field is a JSON object, expected an array',
            );
        }

        $parsed = [];

        foreach ($keys as $index => $jwk) {
            if (!\is_array($jwk)) {
                throw JwtException::fromMalformedJwks(
                    reason: \sprintf(
                        'entry at index %d is not a JSON object',
                        $index,
                    ),
                );
            }

            /** @var array<string, mixed> $jwk */
            $parsed[] = JwkParser::parse(
                jwk: $jwk,
            );
        }

        return new KeySet(...$parsed);
    }
}
