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

use const Castor\Http\Router\PARAMS_ATTR;
use const Castor\Http\Router\PATH_ATTR;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @covers \Castor\Http\Route
 */
class RouteTest extends TestCase
{
    public function testItThrowsNotImplemented(): void
    {
        $router = $this->createStub(Router::class);
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);

        $route = new Route($router);
        $this->expectException(ProtocolError::class);
        $route->process($request, $handler);
    }

    public function testItProcessMethodsOnly(): void
    {
        $router = $this->createStub(Router::class);
        $response = $this->createStub(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $routeHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $route = new Route($router, ['PUT']);
        $route->handler($routeHandler);

        $request->expects($this->once())
            ->method('getMethod')
            ->willReturn('PUT')
        ;
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn('/')
        ;
        $request->expects($this->exactly(2))
            ->method('getAttribute')
            ->withConsecutive([PATH_ATTR], [PARAMS_ATTR])
            ->willReturn(null)
        ;
        $request->expects($this->exactly(2))
            ->method('withAttribute')
            ->withConsecutive([PATH_ATTR, '/'], [PARAMS_ATTR, []])
            ->willReturn($request)
        ;
        $routeHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response)
        ;

        $route->process($request, $nextHandler);
    }

    public function testItProcessPathOnly(): void
    {
        $router = $this->createStub(Router::class);
        $response = $this->createStub(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $routeHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $route = new Route($router);
        $route->path('/users')->handler($routeHandler);

        $request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn('/users/123')
        ;
        $request->expects($this->exactly(2))
            ->method('getAttribute')
            ->withConsecutive([PATH_ATTR], [PARAMS_ATTR])
            ->willReturn(null)
        ;
        $request->expects($this->exactly(2))
            ->method('withAttribute')
            ->withConsecutive([PATH_ATTR, '/123'], [PARAMS_ATTR, []])
            ->willReturn($request)
        ;
        $nextHandler->expects($this->never())
            ->method('handle')
        ;
        $routeHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response)
        ;

        $route->process($request, $nextHandler);
    }

    public function testItProcessMethodAndPath(): void
    {
        $router = $this->createStub(Router::class);
        $response = $this->createStub(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $routeHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $route = new Route($router);
        $route->method('GET')->path('/users')->handler($routeHandler);

        $request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn('/users')
        ;
        $request->expects($this->exactly(2))
            ->method('getAttribute')
            ->withConsecutive([PATH_ATTR], [PARAMS_ATTR])
            ->willReturn(null)
        ;
        $request->expects($this->exactly(2))
            ->method('withAttribute')
            ->withConsecutive([PATH_ATTR, '/'], [PARAMS_ATTR, []])
            ->willReturn($request)
        ;
        $nextHandler->expects($this->never())
            ->method('handle')
        ;
        $routeHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response)
        ;

        $route->process($request, $nextHandler);
    }
}
