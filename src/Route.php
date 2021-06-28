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

use MNC\PathToRegExpPHP\NoMatchException;
use MNC\PathToRegExpPHP\PathRegExp;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Class Route.
 */
class Route extends Path
{
    public const ALLOWED_METHODS_ATTR = 'castor.router.allowed_methods';

    /**
     * @var string[]
     */
    private array $methods;

    /**
     * Route constructor.
     */
    public function __construct(array $methods, PathRegExp $path, Handler $handler)
    {
        parent::__construct($path, $handler);
        $this->methods = $methods;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $path = $this->extractPathFromRequest($request);
        $methodMatches = $this->methodMatches($request->getMethod());

        try {
            $request = $this->matchPath($request, $path);
        } catch (NoMatchException $e) {
            return $handler->handle($request);
        }

        if (!$methodMatches) {
            $request = $this->storeAllowedMethod($request, $this->methods);

            return $handler->handle($request);
        }

        return $this->handler->handle($request);
    }

    protected function methodMatches(string $method): bool
    {
        return \in_array($method, $this->methods, true);
    }

    protected function storeAllowedMethod(Request $request, array $methods): Request
    {
        $allowedMethods = $request->getAttribute(self::ALLOWED_METHODS_ATTR, []);

        return $request->withAttribute(
            self::ALLOWED_METHODS_ATTR,
            array_unique(array_merge($allowedMethods, $methods))
        );
    }
}
