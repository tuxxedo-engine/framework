<?php

declare(strict_types=1);

namespace
{
    use Tuxxedo\Application\ApplicationFactory;
    use Tuxxedo\Http\Request\RequestFactory;
    use Tuxxedo\Http\Request\RequestHandlerInterface;
    use Tuxxedo\Http\Request\RequestInterface;
    use Tuxxedo\Http\Response\ResponseInterface;
    use Tuxxedo\Middleware\MiddlewareInterface;

    require_once __DIR__ . '/../vendor/autoload.php';

    class M1 implements MiddlewareInterface
    {
        public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            return $handler->handle($request)->withBody(static::class);
        }
    }

    class M2 extends M1
    {
    }

    $app = ApplicationFactory::createFromDirectory(
        directory: __DIR__ . '/../app',
    );

    $app->middleware(new M1());
    $app->middleware(static fn(): MiddlewareInterface => new M2());

    $app->container->persistent(RequestFactory::createFromEnvironment());

    echo '<pre>';
    $app->run();
    echo '</pre>';
}
