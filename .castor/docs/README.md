Castor Router Documentation
===========================

## Basic Usage

Creating a router is extremely simple:

```php
<?php

$router = Castor\Http\Router::create();
```

Castor router implements `Psr\Http\Server\RequestHandlerInterface` so you can use it to handle any PRS-7 Server Request.

```php
<?php

$router = Castor\Http\Router::create();

$response = $router->handle($aRequest);
```

> NOTE: An empty router will throw a `Castor\Http\ProtocolError` when its `handle` method is called.

You can add routes by calling `method` and `path` methods in the router instance and passing a handler.

```php
<?php

$router = $router = Castor\Http\Router::create();
$router->method('GET')->path('/users')->handler($listUsersHandler);
$router->method('GET')->path('/users/:id')->handler($findUserHandler);
$router->method('POST')->path('/users')->handler($createUserHandler);
$router->method('DELETE')->path('/users/:id')->handler($deleteUserHandler);
```

As you can see, you can pass routing parameters using `:<param_name>` notation when defining your route.

You can retrieve routing parameters using the `Castor\Http\Router\params` function and passing a Psr Server Request.

```php
<?php

use Psr\Http\Server\RequestHandlerInterface as PsrHandler;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use function Castor\Http\Router\params;

class MyHandler implements PsrHandler
{
    public function handle(PsrRequest $request): PsrResponse
    {
        $id = params($request)['id'] ?? null;
        // TODO: Do something with the id and return a response.
    }
}
```

## Path Handlers

As we have shown above, you can create a Route that responds to a method-path match with a specific handler. But you
can also create a Route that executes a handler upon matching a Path. Just call `path` without calling `method`.

```php
<?php

$router = Castor\Http\Router::create();
$router->path('/users')->handler($aHandler);
```

The `$aHandler` handler will be executed when the path matches `/users`.

## Mounting Routers

By using path matching, you can mount routers on routers and build a routing tree very easily.

```php
$routerOne = Castor\Http\Router::create();
$routerOne->method('GET')->handler($aHandler);
$routerOne->method('GET')->path('/:id')->handler($aHandler);

$routerTwo = Castor\Http\Router::create();
$routerTwo->path('/users')->handler($routerOne);
```

Here we mount `$routerOne` into the `/users` path in `$routerTwo`, which causes all the `$routerOne` routes to match
under `/users` path. For instance, the second route with id will match a `GET /users/1234` request.

## Error Handling

Once you have built all of your routes, we recommend wrapping the router into a `Castor\Http\ErrorHandler`. You will
need a Psr Response Factory, and passing a logger is highly recommended. This will print debugging information as well
as normalizing http responses.