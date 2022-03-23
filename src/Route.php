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

use const Castor\Http\Router\ALLOWED_METHODS_ATTR;
use const Castor\Http\Router\PATH_ATTR;
use MNC\PathToRegExpPHP\NoMatchException;
use MNC\PathToRegExpPHP\PathRegExpFactory;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

/**
 * Class Route.
 */
class Route implements PsrMiddleware
{
    private PsrHandler $handler;
    /**
     * @var string[]
     */
    private array $methods;
    private string $pattern;

    /**
     * Route constructor.
     */
    public function __construct(PsrHandler $handler, array $methods = [], string $pattern = '/')
    {
        $this->handler = $handler;
        $this->methods = $methods;
        $this->pattern = $pattern;
    }

    public function process(PsrRequest $request, PsrHandler $handler): PsrResponse
    {
        $hasMethods = [] !== $this->methods;
        $methodMatches = in_array($request->getMethod(), $this->methods, true);

        $path = $this->extractPathFromRequest($request);

        try {
            $modifyRequest = $this->matchPath($path, $hasMethods);
        } catch (NoMatchException $e) {
            return $handler->handle($request);
        }

        if ($hasMethods && !$methodMatches) {
            $request = $this->storeAllowedMethod($request, $this->methods);

            return $handler->handle($request);
        }

        return $this->handler->handle($modifyRequest($request));
    }

    protected function storeAllowedMethod(PsrRequest $request, array $methods): PsrRequest
    {
        $allowedMethods = $request->getAttribute(ALLOWED_METHODS_ATTR, []);

        return $request->withAttribute(
            ALLOWED_METHODS_ATTR,
            array_unique(array_merge($allowedMethods, $methods))
        );
    }

    protected function extractPathFromRequest(PsrRequest $request): string
    {
        $path = $request->getAttribute(PATH_ATTR) ?? $request->getUri()->getPath();
        if ('' !== $path) {
            $path = '/';
        }

        return $path;
    }

    /**
     * @throws NoMatchException
     *
     * @return callable(PsrRequest): PsrRequest
     */
    protected function matchPath(string $path, bool $full): callable
    {
        $result = PathRegExpFactory::create($this->pattern, $full ? 2 : 0)->match($path);

        return static function (PsrRequest $request) use ($result, $path): PsrRequest {
            // Modify the path to match and store it in the request.
            $path = str_replace($result->getMatchedString(), '', $path);
            if ('' === $path) {
                $path = '/';
            }
            $request = $request->withAttribute(PATH_ATTR, $path);

            // Store the attributes in the request
            foreach ($result->getValues() as $attr => $value) {
                $request = $request->withAttribute($attr, $value);
            }

            return $request;
        };
    }
}
