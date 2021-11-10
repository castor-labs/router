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

use function Castor\Http\Router\params;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 * @covers \Castor\Http\Router
 */
class RouterTest extends IntegrationTestCase
{
    public function testItThrowsEmptyStack(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $router = Router::create();
        $this->expectException(ProtocolError::class);
        $router->handle($request);
    }

    public function testMatchesGetRequest(): void
    {
        $handler = function (ServerRequestInterface $req): ResponseInterface {
            $id = params($req)['id'] ?? null;
            self::assertSame('1234', $id);

            return $this->createResponse('OK');
        };

        $router = Router::create();
        $router->method('GET')->path('/users/:id')->handler($this->functionHandler($handler));

        $response = $router->handle($this->createRequest('GET', 'https://example.com/users/1234'));
        self::assertSame('OK', (string) $response->getBody());
    }
}
