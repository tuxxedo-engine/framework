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

namespace Tuxxedo\Http\Client\Auth;

use Tuxxedo\Http\Client\Body\FormBody;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Url\UrlException;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\InstantInterface;

readonly class TokenEndpoint
{
    public function __construct(
        public UrlInterface|string $url,
        public string $clientId = '',
        #[\SensitiveParameter] public ?string $clientSecret = null,
        public bool $credentialsInBody = false,
    ) {
    }

    /**
     * @param array<string, string> $fields
     *
     * @throws HttpClientException
     * @throws SerializerException
     * @throws UrlException
     */
    public function request(
        HttpClientInterface $httpClient,
        array $fields,
        InstantInterface $now,
        ?string $refreshToken = null,
    ): AccessTokenInterface {
        $headers = [
            new Header('Accept', 'application/json'),
        ];

        if ($this->clientId !== '') {
            if ($this->credentialsInBody || $this->clientSecret === null) {
                $fields['client_id'] = $this->clientId;

                if ($this->clientSecret !== null) {
                    $fields['client_secret'] = $this->clientSecret;
                }
            } else {
                $headers[] = self::basic($this->clientId, $this->clientSecret);
            }
        }

        $request = new ClientRequest(
            method: Method::POST,
            url: $this->url,
            headers: $headers,
            body: new FormBody($fields),
        );

        $response = $httpClient->send($request);

        if ($response->statusCode < 200 || $response->statusCode > 299) {
            throw HttpClientException::fromTokenRequestFailed($request, $response);
        }

        try {
            $data = $response->body->getJson(associative: true);
        } catch (\JsonException $exception) {
            throw HttpClientException::fromTokenRequestFailed($request, $response, $exception);
        }

        if (!\is_array($data) || !\is_string($data['access_token'] ?? null) || $data['access_token'] === '') {
            throw HttpClientException::fromTokenRequestFailed($request, $response);
        }

        $expiresIn = $data['expires_in'] ?? null;
        $tokenType = $data['token_type'] ?? null;
        $newRefreshToken = $data['refresh_token'] ?? null;

        return new AccessToken(
            accessToken: $data['access_token'],
            tokenType: \is_string($tokenType) && $tokenType !== ''
                ? $tokenType
                : 'Bearer',
            expiresAt: \is_int($expiresIn) || (\is_string($expiresIn) && \preg_match('/^\d+$/', $expiresIn) === 1)
                ? $now->plus(Duration::fromSeconds(\max(0, (int) $expiresIn)))
                : null,
            refreshToken: \is_string($newRefreshToken) && $newRefreshToken !== ''
                ? $newRefreshToken
                : $refreshToken,
        );
    }

    private static function basic(
        string $clientId,
        #[\SensitiveParameter] string $clientSecret,
    ): HeaderInterface {
        return new Header(
            'Authorization',
            'Basic ' . \base64_encode(\urlencode($clientId) . ':' . \urlencode($clientSecret)),
        );
    }
}
