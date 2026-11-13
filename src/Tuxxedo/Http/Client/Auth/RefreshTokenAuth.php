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

use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponseInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\SystemClock;

class RefreshTokenAuth implements RefreshableAuthInterface
{
    private readonly TokenEndpoint $endpoint;
    private readonly ClockInterface $clock;
    private bool $forceRefresh = false;

    public function __construct(
        UrlInterface|string $tokenUrl,
        string $clientId,
        private readonly TokenStoreInterface $store,
        #[\SensitiveParameter] ?string $clientSecret = null,
        bool $credentialsInBody = false,
        ?ClockInterface $clock = null,
    ) {
        $this->endpoint = new TokenEndpoint(
            url: $tokenUrl,
            clientId: $clientId,
            clientSecret: $clientSecret,
            credentialsInBody: $credentialsInBody,
        );

        $this->clock = $clock ?? new SystemClock();
    }

    public function apply(
        ClientRequestInterface $request,
        HttpClientInterface $httpClient,
    ): ClientRequestInterface {
        $now = $this->clock->now();
        $token = $this->store->get();

        if ($token === null) {
            throw HttpClientException::fromMissingToken($request);
        }

        if ($this->forceRefresh || $token->isExpired($now)) {
            if ($token->refreshToken === null) {
                throw HttpClientException::fromMissingToken($request);
            }

            $token = $this->endpoint->request(
                httpClient: $httpClient,
                fields: [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $token->refreshToken,
                ],
                now: $now,
                refreshToken: $token->refreshToken,
            );

            $this->store->store($token);
            $this->forceRefresh = false;
        }

        return $request->withHeader(
            header: new Header('Authorization', $token->tokenType . ' ' . $token->accessToken),
            replace: true,
        );
    }

    public function refresh(
        ClientResponseInterface $response,
        HttpClientInterface $httpClient,
    ): bool {
        if ($response->statusCode !== 401 || $this->store->get()?->refreshToken === null) {
            return false;
        }

        $this->forceRefresh = true;

        return true;
    }
}
