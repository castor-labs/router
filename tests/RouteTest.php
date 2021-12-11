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
    public function testItProcessMethodsOnly(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $routeHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $route = new Route($routeHandler, ['PUT'], '/');

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
        $request->expects($this->once())
            ->method('getAttribute')
            ->with(PATH_ATTR)
            ->willReturn(null)
        ;
        $request->expects($this->once())
            ->method('withAttribute')
            ->with(PATH_ATTR, '/')
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
        $response = $this->createStub(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $routeHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $route = new Route($routeHandler, [], '/users');

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
        $request->expects($this->once())
            ->method('getAttribute')
            ->with(PATH_ATTR)
            ->willReturn(null)
        ;
        $request->expects($this->once())
            ->method('withAttribute')
            ->with(PATH_ATTR, '/123')
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
        $response = $this->createStub(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $routeHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $route = new Route($routeHandler, ['GET'], '/users');

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
        $request->expects($this->once())
            ->method('getAttribute')
            ->with(PATH_ATTR)
            ->willReturn(null)
        ;
        $request->expects($this->once())
            ->method('withAttribute')
            ->with(PATH_ATTR, '/')
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
