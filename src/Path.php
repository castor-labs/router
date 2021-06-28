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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Class Path.
 *
 * Path allows to prepend middleware with a path and extract it if matches.
 */
class Path implements MiddlewareInterface
{
    public const PATH_ATTR = 'castor.router.path';

    protected PathRegExp $path;
    protected Handler $handler;

    /**
     * Route constructor.
     */
    public function __construct(PathRegExp $path, Handler $handler)
    {
        $this->path = $path;
        $this->handler = $handler;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $path = $this->extractPathFromRequest($request);

        try {
            $modifyRequest = $this->matchPath($request, $path);
        } catch (NoMatchException $e) {
            return $handler->handle($request);
        }

        return $this->handler->handle($modifyRequest($request));
    }

    protected function extractPathFromRequest(Request $request): string
    {
        $path = $request->getAttribute(self::PATH_ATTR) ?? $request->getUri()->getPath();
        if ('' === $path) {
            $path = '/';
        }

        return $path;
    }

    /**
     * @throws NoMatchException
     *
     * @return callable(Request): Request
     */
    protected function matchPath(Request $request, string $path): callable
    {
        $result = $this->path->match($path);

        return static function (Request $request) use ($result, $path): Request {
            // Modify the path to match and store it in the request.
            $path = str_replace($result->getMatchedString(), '', $path);
            if ('' === $path) {
                $path = '/';
            }
            $request = $request->withAttribute(self::PATH_ATTR, $path);

            // Store the attributes in the request
            foreach ($result->getValues() as $attr => $value) {
                $request = $request->withAttribute($attr, $value);
            }

            return $request;
        };
    }
}
