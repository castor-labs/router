Castor Router
=============

A composable Express-like router for PSR-7 based PHP applications

```
composer require castor/router
```

## Basic Usage

You can simply create an instance of the router and everything comes ready to use.

You can register routes calling methods after the most popular request verbs, passing
a path and an instance of `Psr\Http\Server\RequestHandlerInterface`.

```php
<?php

$router = Castor\Http\Router::create();
$router->get('/', new HelloRequestHandler());
```

You can also register middleware with the `use` method and passing a
`Psr\Http\Server\MiddlewareInterface`.

```php
<?php

$router = Castor\Http\Router::create();
$router->use(new RequestLogging());
$router->get('/', new HelloRequestHandler());
```

## Use Case Questions

### Can I register lazy handlers from a container?

Yes, you can. We provide a `Castor\Http\Handler` class that helps with lazy
registration of handlers. We also provide a `Castor\Http\Middleware`
to register middleware on a lazy fashion.

```php
<?php

use Castor\Http\Handler;
use Castor\Http\Middleware;

$router = Castor\Http\Router::create();
$router->use(Middleware::lazy($container, RequestLogging::class));
$router->get('/', Handler::lazy($container, HelloRequestHandler::class));
```

Lazy middleware and handlers are only fetched from the container when they are
executed, avoiding wasting resources by initializing handlers that will never
be used in the request.

### Can I implement the controller pattern?

Yes, you can. Controllers are handlers that call a specific method. You can pass
the method as a third optional argument in the `Castor\Http\Handler::lazy` call.

```php
<?php

use Castor\Http\Handler;

$router = Castor\Http\Router::create();
$router->get('/users', Handler::lazy($container, UserController::class, 'index'));
```

### Can I use routing params?

Yes, you can. Routing parameter matching is powered by `mnavarrocarter/path-to-regexp-php`,
a port of Node js path to regex library. You must read [the documentation](https://github.com/mnavarrocarter/path-to-regexp-php#parameters)
to understand the parameter syntax, but if you have used Express JS before, you already
should know it.

```php
<?php

use Castor\Http\Handler;

$router = Castor\Http\Router::create();
$router->get('/users/:id', Handler::lazy($container, UserController::class, 'show'));
```

Routing parameters are available in the request attributes under the parameter name. In
this case, the parameter will be `id`.

### Can I use closures instead of actual handlers?

Yes, you can. Just use the `Castor\Http\Handler::reflect` or `Castor\Http\Handler::__construct`
methods to wrap a closure into a `Psr\Http\Server\RequestHandlerInterface` instance.

If you call the `reflect` method, the routing parameters and other services
contained in the request attributes will be injected in the method call.

```php

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Castor\Http\Handler;

function hello(PsrRequest $request, string $name = null): PsrResponse {
    $name = $name ?? 'World';
    return new Response("Hello $name!", 200);
}

$router = Castor\Http\Router::create();
$router->get('/greet/:name?', Handler::reflect(Closure::fromCallable('hello'), $container));

```

Please note that for routing parameters to be injected they need to have the same
name in the method than in the route definition. In this case, the name is `name`.

### How does automatic injection work?

All lazy handlers are instantiated using reflection. When this happens,
`Castor\Http\Router` looks at the request attributes and tries to inject 
anything that matches a parameter name or a type. This means you can save objects
into the request attributes in previous middleware and then retrieve them
just by type-hinting them into a method call.

This is very useful for authentication and other purposes.

This is done automatically for you when you use the `Castor\Http\Handler::lazy`
method.

### Can I register routes in an easier fashion?

Yes, you can. To avoid all the verbosity of registering routes, this library
provides a `Castor\Http\ArrayTreeRouteLoader` class that eases the registering
of routes significantly.

```php
<?php

use Castor\Http\Router;
use Castor\Http\ArrayTreeRouteLoader;

$tree = [
    // This is a middleware that will run before anything
    'RequestLogger',
    // This will become a child router under the /api path.
    '/api' => [
        // This is a middleware that will run only in the /api path.
        'ApiAuthenticator',
        'ParseJsonBody',
        // This will execute the api home handler
        'GET /' => 'Api\Home',
        // This will be a child router of the api child router
        '/users' => [
            // This is a route that will hit the Api\UserController class and its index method.
            'GET /' => 'Api\UserController@index',
            'GET /:id' => 'Api\UserController@show', // Same, but show method.
            'POST' => 'Api\UserController@create' // Same, but create method.
        ],
        
    ],
    // This is a route for the root path
    'GET /' => 'HomeController@html',
];

$router = Router::create();
$loader = new ArrayTreeRouteLoader($container);
$loader->addBaseNamespace('App\\Http\\Handlers'); // This will append a base namespace to those class names.
$loader->load($router, $tree);
```

This router is built in such a way that if `/api` does not match, every single
route under that node will be ignored, and the next sibling route will be tried.
This means that you are in absolute control of your router's performance, and you
will be able to give priority to hot routes.

There is just one principle to remember: every route and middleware is executed
in the same order it was registered.

### Can I format your hard drive?

No, you can not. I need those bits to be 1 and 0.
