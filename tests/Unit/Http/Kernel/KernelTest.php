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

namespace Unit\Http\Kernel;

use PHPUnit\Framework\TestCase;
use Support\Http\Kernel\RecordingErrorHandler;
use Support\Http\Kernel\StubDispatcher;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Http\Response\StubResponseEmitter;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Config\Config;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\PrefersResponseCodeInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\StaticRouter;

class KernelTest extends TestCase
{
    /**
     * @param list<(\Closure(): MiddlewareInterface)|MiddlewareInterface> $middleware
     * @param array<class-string<\Throwable>, list<(\Closure(): ErrorHandlerInterface)|ErrorHandlerInterface>> $exceptionHandlers
     * @param list<(\Closure(): ErrorHandlerInterface)|ErrorHandlerInterface> $defaultExceptionHandlers
     */
    private function makeKernel(
        ?StubResponseEmitter $emitter = null,
        ?StubDispatcher $dispatcher = null,
        ?StaticRouter $router = null,
        array $middleware = [],
        array $exceptionHandlers = [],
        array $defaultExceptionHandlers = [],
    ): Kernel {
        return new Kernel(
            container: new Container(),
            config: new Config(),
            emitter: $emitter ?? new StubResponseEmitter(),
            dispatcher: $dispatcher ?? new StubDispatcher(new Response()),
            router: $router ?? new StaticRouter(
                routes: [],
            ),
            middleware: $middleware,
            exceptionHandlers: $exceptionHandlers,
            defaultExceptionHandlers: $defaultExceptionHandlers,
        );
    }

    private function makeRequest(
        Method $method = Method::GET,
        string $path = '/test',
    ): Request {
        return new Request(
            serializerManager: SerializerManagerFactory::create(),
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: $method,
            path: $path,
        );
    }

    private function makeRouter(
        Method|null $method = Method::GET,
        string $path = '/test',
    ): StaticRouter {
        return new StaticRouter(
            routes: [
                new Route(
                    method: $method,
                    path: $path,
                    controller: self::class,
                    action: 'index',
                ),
            ],
        );
    }

    public function testMiddlewareAddsClosure(): void
    {
        $closure = static fn (): MiddlewareInterface => throw new \LogicException('stub');
        $kernel = $this->makeKernel(
            middleware: [
                $closure,
            ],
        );

        self::assertCount(1, $kernel->middleware);
        self::assertSame($closure, $kernel->middleware[0]);
    }

    public function testMiddlewareKeepsInstance(): void
    {
        $instance = new class () implements MiddlewareInterface {
            public function handle(
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface {
                return $next->handle($request, $next);
            }
        };

        $kernel = $this->makeKernel(
            middleware: [
                $instance,
            ],
        );

        self::assertCount(1, $kernel->middleware);
        self::assertSame($instance, $kernel->middleware[0]);
    }

    public function testExceptionHandlerRegistersClosure(): void
    {
        $closure = static fn (): ErrorHandlerInterface => throw new \LogicException('stub');
        $kernel = $this->makeKernel(
            exceptionHandlers: [
                \RuntimeException::class => [
                    $closure,
                ],
            ],
        );

        self::assertArrayHasKey(\RuntimeException::class, $kernel->exceptionHandlers);
        self::assertCount(1, $kernel->exceptionHandlers[\RuntimeException::class]);
    }

    public function testExceptionHandlerKeepsInstance(): void
    {
        $instance = new class () implements ErrorHandlerInterface {
            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                return $response;
            }
        };

        $kernel = $this->makeKernel(
            exceptionHandlers: [
                \RuntimeException::class => [
                    $instance,
                ],
            ],
        );

        self::assertSame($instance, $kernel->exceptionHandlers[\RuntimeException::class][0]);
    }

    public function testDefaultExceptionHandlerRegistersClosure(): void
    {
        $closure = static fn (): ErrorHandlerInterface => throw new \LogicException('stub');
        $kernel = $this->makeKernel(
            defaultExceptionHandlers: [
                $closure,
            ],
        );

        self::assertCount(1, $kernel->defaultExceptionHandlers);
    }

    public function testRunDispatchesRouteAndEmitsResponse(): void
    {
        $emitter = new StubResponseEmitter();
        $response = new Response(
            body: 'ok',
        );

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher($response),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame($response, $emitter->lastResponse);
    }

    public function testRunEmits404WhenRouteNotFound(): void
    {
        $emitter = new StubResponseEmitter();

        $kernel = $this->makeKernel(
            emitter: $emitter,
            router: new StaticRouter(
                routes: [],
            ),
        );

        $kernel->run($this->makeRequest());

        self::assertNotNull($emitter->lastResponse);
        self::assertSame(ResponseCode::NOT_FOUND, $emitter->lastResponse->responseCode);
    }

    public function testRunResolvesRequestFromContainerWhenNull(): void
    {
        $emitter = new StubResponseEmitter();
        $response = new Response(
            body: 'from container',
        );

        $container = new Container();

        $container->singleton(
            class: $this->makeRequest(),
        );

        $kernel = new Kernel(
            container: $container,
            config: new Config(),
            emitter: $emitter,
            dispatcher: new StubDispatcher($response),
            router: $this->makeRouter(),
        );

        $kernel->run();

        self::assertSame($response, $emitter->lastResponse);
    }

    public function testRunCallsExceptionHandlerOnDispatcherThrow(): void
    {
        $emitter = new StubResponseEmitter();

        $handler = new class () implements ErrorHandlerInterface {
            public bool $called = false;

            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                $this->called = true;

                return $response;
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                result: new \RuntimeException('test'),
            ),
            router: $this->makeRouter(),
            exceptionHandlers: [
                \RuntimeException::class => [
                    $handler,
                ],
            ],
        );

        $kernel->run($this->makeRequest());

        self::assertTrue($handler->called);
    }

    public function testRunExecutesRegisteredMiddleware(): void
    {
        $emitter = new StubResponseEmitter();

        $middleware = new class () implements MiddlewareInterface {
            public bool $called = false;

            public function handle(
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface {
                $this->called = true;

                return $next->handle($request, $next);
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(new Response(body: 'ok')),
            router: $this->makeRouter(),
            middleware: [
                $middleware,
            ],
        );

        $kernel->run($this->makeRequest());

        self::assertTrue($middleware->called);
        self::assertSame('ok', $emitter->lastResponse?->body);
    }

    public function testRunResolvesClosureMiddlewareThroughContainer(): void
    {
        $emitter = new StubResponseEmitter();

        $middleware = new class () implements MiddlewareInterface {
            public bool $called = false;

            public function handle(
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface {
                $this->called = true;

                return $next->handle($request, $next);
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(new Response(body: 'ok')),
            router: $this->makeRouter(),
            middleware: [
                static fn (): MiddlewareInterface => $middleware,
            ],
        );

        $kernel->run($this->makeRequest());

        self::assertTrue($middleware->called);
        self::assertSame('ok', $emitter->lastResponse?->body);
    }

    public function testRunEmitsResponseExceptionDirectly(): void
    {
        $emitter = new StubResponseEmitter();

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(HttpException::fromUnauthorized()),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertNotNull($emitter->lastResponse);
        self::assertSame(ResponseCode::UNAUTHORIZED, $emitter->lastResponse->responseCode);
    }

    public function testHandleExceptionUsesResponseCodeFromOriginalException(): void
    {
        $emitter = new StubResponseEmitter();

        $exception = new class () extends \Exception implements PrefersResponseCodeInterface {
            public ?ResponseCode $responseCode {
                get {
                    return ResponseCode::FORBIDDEN;
                }
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher($exception),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame(ResponseCode::FORBIDDEN, $emitter->lastResponse?->responseCode);
    }

    public function testHandleExceptionFindsResponseExceptionInChain(): void
    {
        $emitter = new StubResponseEmitter();

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                new \RuntimeException(
                    message: 'outer',
                    previous: HttpException::fromUnauthorized(),
                ),
            ),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame(ResponseCode::UNAUTHORIZED, $emitter->lastResponse?->responseCode);
    }

    public function testHandleExceptionFindsPreferredResponseCodeInChain(): void
    {
        $emitter = new StubResponseEmitter();

        $inner = new class () extends \Exception implements PrefersResponseCodeInterface {
            public ?ResponseCode $responseCode {
                get {
                    return ResponseCode::NOT_ACCEPTABLE;
                }
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                new \RuntimeException(
                    message: 'outer',
                    previous: $inner,
                ),
            ),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame(ResponseCode::NOT_ACCEPTABLE, $emitter->lastResponse?->responseCode);
    }

    public function testHandleExceptionFallsBackTo500WhenNothingMatches(): void
    {
        $emitter = new StubResponseEmitter();

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                result: new \RuntimeException('plain'),
            ),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $emitter->lastResponse?->responseCode);
    }

    public function testDefaultExceptionHandlerKeepsInstance(): void
    {
        $instance = new class () implements ErrorHandlerInterface {
            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                return $response;
            }
        };

        $kernel = $this->makeKernel(
            defaultExceptionHandlers: [
                $instance,
            ],
        );

        self::assertCount(1, $kernel->defaultExceptionHandlers);
        self::assertSame($instance, $kernel->defaultExceptionHandlers[0]);
    }

    public function testDefaultExceptionHandlerInstanceIsCalledOnException(): void
    {
        $emitter = new StubResponseEmitter();

        $handler = new class () implements ErrorHandlerInterface {
            public bool $called = false;

            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                $this->called = true;

                return $response;
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                result: new \RuntimeException('test'),
            ),
            router: $this->makeRouter(),
            defaultExceptionHandlers: [
                $handler,
            ],
        );

        $kernel->run($this->makeRequest());

        self::assertTrue($handler->called);
    }

    public function testRunResolvesClosureHandlersThroughContainer(): void
    {
        $emitter = new StubResponseEmitter();
        $handler = new RecordingErrorHandler();

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                result: new \RuntimeException('test'),
            ),
            router: $this->makeRouter(),
            defaultExceptionHandlers: [
                static fn (): ErrorHandlerInterface => $handler,
            ],
        );

        $kernel->run($this->makeRequest());

        self::assertSame(1, $handler->callCount);
    }
}
