<?php

declare(strict_types=1);

/**
 * @project Castor Router
 * @link https://github.com/castor-labs/router
 * @package castor/router
 * @author Matias Navarro-Carter mnavarrocarter@gmail.com
 * @license MIT
 * @copyright 2021 CastorLabs Ltd
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Castor\Http;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Class Middleware allows the easy registration of lazy middleware from a service
 * container.
 *
 * It provides useful methods to also reflect closures and inject arguments into
 * controllers.
 */
final class Middleware implements MiddlewareInterface
{
    /**
     * @psalm-var Closure(Request, Handler): Response
     */
    private Closure $closure;

    /**
     * Middleware constructor.
     *
     * @psalm-param Closure(Request, Handler): Response $closure
     */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * @psalm-return callable(string): Middleware
     */
    public static function factory(ContainerInterface $container): callable
    {
        return static function (string $service) use ($container): Middleware {
            return self::lazy($container, $service);
        };
    }

    public static function lazy(ContainerInterface $container, string $service): Middleware
    {
        return new self(static function (Request $request, Handler $handler) use ($container, $service): Response {
            $middleware = $container->get($service);
            if (is_callable($middleware)) {
                $middleware = new self(Closure::fromCallable($middleware));
            }
            if (!$middleware instanceof MiddlewareInterface) {
                throw new \RuntimeException(sprintf(
                    'Middleware "%s" fetched from container must be a callable or an instance of %s',
                    $service,
                    MiddlewareInterface::class
                ));
            }

            return $middleware->process($request, $handler);
        });
    }

    public function process(Request $request, Handler $handler): Response
    {
        return ($this->closure)($request, $handler);
    }
}
