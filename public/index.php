<?php

declare(strict_types=1);

namespace
{
    use Tuxxedo\Application\ApplicationFactory;
    use Tuxxedo\Application\ErrorHandlerInterface;
    use Tuxxedo\Http\Request\RequestFactory;
    use Tuxxedo\Http\Request\RequestInterface;
    use Tuxxedo\Http\Response\ResponseInterface;
    use Tuxxedo\Middleware\MiddlewareInterface;

    require_once __DIR__ . '/../vendor/autoload.php';

    class M1 implements MiddlewareInterface
    {
        public function handle(
            RequestInterface $request,
            ResponseInterface $response,
        ): ResponseInterface {
            return $response->withBody(
                join(
                    PHP_EOL,
                    [
                        $response->body,
                        static::class,
                    ],
                ),
            );
        }
    }

    class M2 extends M1
    {
    }

    class M3 implements MiddlewareInterface
    {
        public function handle(
            RequestInterface $request,
            ResponseInterface $response,
        ): ResponseInterface {
            throw new Exception('Always throws');
        }
    }

    $app = ApplicationFactory::createFromDirectory(
        directory: __DIR__ . '/../app',
    );

    $app->middleware(new M1());
    $app->middleware(static fn(): MiddlewareInterface => new M2());
    $app->middleware(new M3());

    $app->defaultExceptionHandler(
        new class implements ErrorHandlerInterface {
            public function handle(
                RequestInterface $request,
                \Throwable $exception,
            ): void {
                printf(
                    "[Default handler] Caught exception %s[%s]: %s\n",
                    $exception::class,
                    $exception->getCode(),
                    $exception->getMessage(),
                );
            }
        },
    );

    $app->whenException(
        Exception::class,
        new class implements ErrorHandlerInterface {
            public function handle(
                RequestInterface $request,
                \Throwable $exception,
            ): void {
                printf(
                    "[Specific handler] Caught exception %s[%s]: %s\n",
                    $exception::class,
                    $exception->getCode(),
                    $exception->getMessage(),
                );
            }
        },
    );

    $app->container->persistent(RequestFactory::createFromEnvironment());

    echo '<pre>';
    $app->run();
    echo '</pre>';
}
