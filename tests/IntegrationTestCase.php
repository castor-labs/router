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

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class IntegrationTestCase extends TestCase
{
    private static ?Psr17Factory $factory = null;

    protected function createRequest(string $method, string $uri): ServerRequestInterface
    {
        return self::getPsr17Factory()->createServerRequest($method, $uri);
    }

    protected function functionHandler(callable $callable): RequestHandlerInterface
    {
        return new CallableHandler(\Closure::fromCallable($callable));
    }

    protected function createResponse(string $text): ResponseInterface
    {
        return self::getPsr17Factory()->createResponse(200)
            ->withBody(self::getPsr17Factory()->createStream($text))
            ->withHeader('Content-Type', 'text/plain')
        ;
    }

    private static function getPsr17Factory(): Psr17Factory
    {
        if (null === self::$factory) {
            self::$factory = new Psr17Factory();
        }

        return self::$factory;
    }
}
