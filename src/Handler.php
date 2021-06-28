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
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;
use ReflectionType;
use RuntimeException;

/**
 * Class Handler allows the easy registration of lazy handlers from a service
 * container.
 *
 * It provides useful methods to also reflect closures and inject arguments into
 * controllers.
 */
final class Handler implements RequestHandler
{
    private Closure $closure;

    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * The factory method returns a callable that can make Handlers calling a class and optionally a method.
     *
     * @return Closure(string,?string): Handler
     */
    public static function factory(ContainerInterface $container): Closure
    {
        return static function (string $service, string $method = null) use ($container): self {
            return self::lazy($container, $service, $method);
        };
    }

    /**
     * Makes a Handler from a DI container service.
     *
     * Optionally, it accepts a method.
     */
    public static function lazy(ContainerInterface $container, string $service, string $method = null): Handler
    {
        return new self(static function (Request $request) use ($container, $service, $method): Response {
            $handler = $container->get($service);
            if (null !== $method) {
                $handler = Closure::fromCallable([$handler, $method]);
            }
            if (is_callable($handler) && !$handler instanceof Closure) {
                $handler = Closure::fromCallable($handler);
            }
            if ($handler instanceof Closure) {
                $handler = self::reflect($handler, $container);
            }
            if (!$handler instanceof RequestHandler) {
                throw new \RuntimeException(sprintf(
                    'Handler "%s" fetched from container must be a callable or an instance of %s',
                    $service,
                    RequestHandler::class
                ));
            }

            return $handler->handle($request);
        });
    }

    /**
     * Takes any callable an performs reflection on it.
     *
     * It scans for parameters in the request attributes. If it finds a matching parameter name or type,
     * it will inject it into the parameters of the function.
     *
     * It can optionally take a ContainerInterface as an argument to resolve services
     * from.
     */
    public static function reflect(Closure $callable, ContainerInterface $container = null): Handler
    {
        return new self(static function (Request $request) use ($callable, $container): Response {
            $function = new ReflectionFunction($callable);
            $arguments = [];
            foreach ($function->getParameters() as $param) {
                $arguments[] = self::resolveParameter($param, $request, $container);
            }

            return ($callable)(...$arguments);
        });
    }

    public function handle(Request $request): Response
    {
        return ($this->closure)($request);
    }

    /**
     * @psalm-suppress UndefinedMethod
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    private static function resolveParameter(ReflectionParameter $param, Request $request, ContainerInterface $container = null)
    {
        $type = $param->getType();
        $name = $param->getName();
        $attrs = $request->getAttributes();

        if (!$type instanceof ReflectionType) {
            // If the argument is not typed, then we can only resolve elements from
            // the context api by name, or any request parameters.
            if (array_key_exists($name, $attrs)) {
                return $attrs[$name] ?? null;
            }
            if ('req' === $name || 'request' === $name) {
                return $request;
            }
            if ($param->isOptional()) {
                return $param->getDefaultValue();
            }
            if ($param->allowsNull()) {
                return null;
            }

            throw new RuntimeException(sprintf(
                'Could not resolve argument %s ($%s). Try using type-hints for better reflection.',
                $param->getPosition(),
                $name,
            ));
        }

        $typeName = $type->getName();

        if ($type->isBuiltin()) {
            // If is a builtin type, we should try the request context or the route params.
            if (array_key_exists($name, $attrs)) {
                return $attrs[$name] ?? null;
            }
            if ($param->isOptional()) {
                return $param->getDefaultValue();
            }
            if ($param->allowsNull()) {
                return null;
            }

            throw new RuntimeException(sprintf(
                'Could not resolve argument %s ($%s) of type %s. Try making the argument optional.',
                $param->getPosition(),
                $name,
                $typeName
            ));
        }

        // We try to find the type in some of the request information.
        if ($request instanceof $typeName) {
            return $request;
        }
        // We try to find the type in the request attributes
        foreach ($attrs as $value) {
            if (is_object($value) && $value instanceof $typeName) {
                return $value;
            }
        }
        if (null !== $container && $container->has($typeName)) {
            return $container->get($typeName);
        }
        // We give it a last shot to optional arguments.
        if ($param->isOptional()) {
            return $param->getDefaultValue();
        }
        if ($param->allowsNull()) {
            return null;
        }

        throw new RuntimeException(sprintf(
            'Could not resolve argument %s ($%s) of type %s. Have you tried using a DI Container?',
            $param->getPosition(),
            $name,
            $typeName
        ));
    }
}
