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

use Tuxxedo\Http\Client\HttpClientInterface;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtManager;
use Tuxxedo\Security\Jwt\JwtManagerInterface;
use Tuxxedo\Security\Jwt\Key\KeyInterface;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\SystemClock;

class JwtAssertionAuth implements RefreshableAuthInterface
{
    private readonly TokenEndpoint $endpoint;
    private readonly DurationInterface $lifetime;
    private readonly ClockInterface $clock;
    private ?AccessTokenInterface $token = null;

    /**
     * @param list<string> $scopes
     */
    public function __construct(
        UrlInterface|string $tokenUrl,
        private readonly string $issuer,
        private readonly string $audience,
        #[\SensitiveParameter] private readonly KeyInterface $key,
        private readonly Algorithm $algorithm = Algorithm::RS256,
        private readonly ?string $subject = null,
        private readonly array $scopes = [],
        ?DurationInterface $lifetime = null,
        private readonly JwtManagerInterface $jwtManager = new JwtManager(),
        ?ClockInterface $clock = null,
    ) {
        $this->endpoint = new TokenEndpoint($tokenUrl);
        $this->lifetime = $lifetime ?? Duration::fromHours(1);
        $this->clock = $clock ?? new SystemClock();
    }

    public function apply(
        ClientRequestInterface $request,
        HttpClientInterface $httpClient,
    ): ClientRequestInterface {
        $now = $this->clock->now();

        if ($this->token === null || $this->token->isExpired($now)) {
            $claims = [
                'iss' => $this->issuer,
                'aud' => $this->audience,
                'iat' => $now->toUnixTimestamp(),
                'exp' => $now->plus($this->lifetime)->toUnixTimestamp(),
            ];

            if ($this->subject !== null) {
                $claims['sub'] = $this->subject;
            }

            $fields = [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            ];

            if (\sizeof($this->scopes) > 0) {
                $scope = \join(' ', $this->scopes);
                $claims['scope'] = $scope;
                $fields['scope'] = $scope;
            }

            $fields['assertion'] = $this->jwtManager->encode(
                claims: $claims,
                algorithm: $this->algorithm,
                key: $this->key,
            )->compact;

            $this->token = $this->endpoint->request($httpClient, $fields, $now);
        }

        return $request->withHeader(
            header: new Header('Authorization', $this->token->tokenType . ' ' . $this->token->accessToken),
            replace: true,
        );
    }

    public function refresh(
        ClientResponseInterface $response,
        HttpClientInterface $httpClient,
    ): bool {
        if ($response->statusCode !== 401 || $this->token === null) {
            return false;
        }

        $this->token = null;

        return true;
    }
}
