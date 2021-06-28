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
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class ArrayTreeRouteLoader loads routes from an array tree using a service
 * container.
 */
final class ArrayTreeRouteLoader implements RouteLoader
{
    private const ROUTE_REGEX = '/^(GET|HEAD|POST|PATCH|PUT|DELETE|OPTIONS)/';

    private ContainerInterface $container;
    private array $baseNamespaces;

    /**
     * ArrayTreeRouteLoader constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->baseNamespaces = [];
    }

    public static function make(ContainerInterface $container): ArrayTreeRouteLoader
    {
        return new self($container);
    }

    /**
     * @return $this
     */
    public function addBaseNamespace(string $baseNamespace): ArrayTreeRouteLoader
    {
        $this->baseNamespaces[] = $baseNamespace;

        return $this;
    }

    public function load(Router $router, array $tree = []): void
    {
        if ([] === $tree) {
            return;
        }
        foreach ($tree as $key => $value) {
            if (is_int($key)) {
                // This is a middleware
                $router->use($this->resolveMiddleware($value));

                continue;
            }
            if (!is_string($key)) {
                throw new InvalidArgumentException('Routing tree array keys can only be integers or strings');
            }
            if ($this->isRoute($key)) {
                // This is a route definition
                [$methods, $path] = explode(' ', $key, 2);
                $router->route(explode('|', $methods), $path, $this->resolveHandler($value));
            }
            if (is_array($value)) {
                // This is a sub router
                $child = $router->child();
                $this->load($child, $value);
                $value = $child;
            }
            if ($value instanceof RequestHandlerInterface) {
                $router->mount($key, $value);
            }
        }
    }

    private function isRoute(string $key): bool
    {
        return 1 === preg_match(self::ROUTE_REGEX, $key);
    }

    /**
     * @param $handler
     */
    private function resolveHandler($handler): RequestHandlerInterface
    {
        if ($handler instanceof Closure) {
            return Handler::reflect($handler, $this->container);
        }
        $service = $handler;
        $method = null;
        if (is_array($handler)) {
            $service = $handler[0] ?? '';
            $method = $handler[1] ?? null;
        }
        if (!is_string($service)) {
            throw new InvalidArgumentException('Class name must be a string');
        }
        if (false !== strpos($service, '@')) {
            $parts = explode('@', $service);
            $service = $parts[0] ?? '';
            $method = $parts[1] ?? null;
        }
        if (false !== strpos($service, '::')) {
            $parts = explode('::', $service);
            $service = $parts[0] ?? '';
            $method = $parts[1] ?? null;
        }

        $service = $this->tryFQCN($service);

        return Handler::make($this->container, $service, $method);
    }

    /**
     * @param $middleware
     */
    private function resolveMiddleware($middleware): MiddlewareInterface
    {
        if ($middleware instanceof Closure) {
            return new Middleware($middleware);
        }
        if (!is_string($middleware)) {
            throw new InvalidArgumentException('Middleware must be a string');
        }
        $middleware = $this->tryFQCN($middleware);

        return Middleware::lazy($this->container, $middleware);
    }

    /**
     * @return string#
     */
    private function tryFQCN(string $className): string
    {
        if (class_exists($className)) {
            return $className;
        }
        foreach ($this->baseNamespaces as $namespace) {
            $className = $namespace.'\\'.$className;
            if (class_exists($className)) {
                return $className;
            }
        }
        // We return. Is probably a service with a string name.
        return $className;
    }
}
