Castor Router Documentation
===========================

## Basic Usage

Creating a router is extremely simple:

```php
<?php

$router = Castor\Http\Router::create();
```

Castor router implements `Psr\Http\Server\RequestHandlerInterface` so you can use it to handle any PSR-7 Server Request.

```php
<?php

$router = Castor\Http\Router::create();

$response = $router->handle($aRequest);
```

> NOTE: An empty router will throw a `Castor\Http\EmptyStackError` when its `handle` method is called.

You can add routes by calling methods named after http methods and passing a path and a handler.

```php
<?php

$router = $router = Castor\Http\Router::create();
$router->get('/users', $listUsersHandler);
$router->get('/users/:id', $findUserHandler);
$router->post('/users', $createUserHandler);
$router->delete('/users/:id', $deleteUserHandler);
```

As you can see, you can pass routing parameters using `:<param_name>` notation when defining your route.

You can retrieve routing parameters by calling the `getAttribute` method on the `$request` and passing the param
name.

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
        $id = $request->getAttribute('id');
        // TODO: Do something with the id and return a response.
    }
}
```

## Path Handlers

As we have shown above, you can create a Route that responds to a method-path match with a specific handler. But you
can also create a Route that executes a handler upon matching a Path. Just call the `path` function in the router
class.

```php
<?php

$router = Castor\Http\Router::create();
$router->path('/users', $aHandler);
```

The `$aHandler` handler will be executed when the path matches `/users`.

## Mounting Routers

By using path matching, you can mount routers on routers and build a routing tree very easily.

```php
$routerOne = Castor\Http\Router::create();
$routerOne->get('/', $aHandler);
$routerOne->get('/:id', $aHandler);

$routerTwo = Castor\Http\Router::create();
$routerTwo->path('/users', $routerOne);
```

Here we mount `$routerOne` into the `/users` path in `$routerTwo`, which causes all the `$routerOne` routes to match
under `/users` path. For instance, the second route with id will match a `GET /users/1234` request.

## Error Handling

Once you have built all of your routes, we recommend wrapping the router into a `Castor\Http\ErrorHandler`. You will
need a Psr Response Factory. This is so the router can respond on errors and not simply throw
exceptions.