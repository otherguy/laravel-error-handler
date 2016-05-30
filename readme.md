[![Latest Stable Version](https://img.shields.io/packagist/v/winternight/laravel-error-handler.svg?style=flat-square)](https://packagist.org/packages/winternight/laravel-error-handler) [![License](https://img.shields.io/dub/l/vibe-d.svg?style=flat-square)](license.md) [![Downloads](https://img.shields.io/packagist/dt/winternight/laravel-error-handler.svg?style=flat-square)](https://packagist.org/packages/winternight/laravel-error-handler)

# Laravel 5 Error Handler

Unlike version 4, Laravel 5 no longer uses [Whoops](https://github.com/filp/whoops "filp/whoops") error handling out of
the box and instead relies on the Symfony error handler, which is far less
informative for developers.

This packages provides a convenient way to get the more informative [Whoops](https://github.com/filp/whoops "filp/whoops") 
error messages back in your Laravel 5 project, along with a few other goodies.

![Exception](screenshot.png "The Whoops Error Handler in Action!" )

## Features

* Optional [Whoops](https://github.com/filp/whoops "filp/whoops") 1.1 or 2.1 exception handler in debug mode
* Standard (and configurable) error views in production mode
* Provides AJAX-compatible JSON error responses in case of an exception (including HTTP exceptions)
* Fires an event for each exception, providing full access to the exception
* Compatibility with [Laravel Debug Bar](https://github.com/barryvdh/laravel-debugbar "barryvdh/laravel-debugbar")

## Installation

To get the latest version of Laravel Error Handler, simply require the project using Composer:

```bash
$ composer require winternight/laravel-error-handler
```

Instead you can of course manually update the `require` block in your `composer.json` and add the `laravel-error-handler` package.

```json
"require": {
    "winternight/laravel-error-handler": "^1"
},
```

### Whoops

Whoops itself is an optional dependency and you can do without Whoops on production. To install it, simply run:

```bash
$ composer require filp/whoops --dev
```

Both Whoops `^1.1` and `^2.1` are supported but I strongly recommend you use the latest version, especially if you already use PHP 7.

## Configuration 

Add the service provider in your `config/app.php` :

```php
...
Winternight\LaravelErrorHandler\ServiceProvider::class,
...
```

You then need to change your `App\Exceptions\Handler` class to extend `Winternight\LaravelErrorHandler\Handlers\ExceptionHandler` rather than extending `Illuminate\Foundation\Exceptions\Handler`.

```php
<?php namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Winternight\LaravelErrorHandler\Handlers\ExceptionHandler as ExceptionHandler;

class Handler extends ExceptionHandler
{
...
```

Thats it — better error handling for your Laravel 5 project!

## Usage

Normally, you don't have to do anything special. As long as debug mode is enabled in your Laravel application (that is, 
`app.debug` is set to `true`) and Whoops is installed, you should see the Whoops error handler.

### Debug Mode vs. Plain Mode

In debug mode, the Whoops handler is being shown. Since it exposes a lot of information about your system, your source 
code and potentially even passwords to your database and other services (depending on what you store in your `.env` file
and thus your environment variables), debug mode should never, *ever* be enabled on a production environment (or any 
environment that is exposed to the outside world).

If `app.debug` is enabled and Whoops is not installed, it will fall back to the Laravel 5 way of displaying exceptions.

Because you will proably need pretty error pages for your application anyway, this package takes care of that for you. 
If debug mode is disabled (`app.debug` is set to `false`), an error view is rendered instead. By default, the view being
rendered is `errors.error` (that will be `views/errors/error.blade.php` for most of us). If you want to change that, you 
can set a view in `config/view.php` like this (this example would use the `views/error/exception.blade.php` view):

```php
return [
    // ...
    'error' => 'errors.exception',
];
```

### HTTP Exceptions

For HTTP exceptions like `404`, `403` and so on, the package will try to find a corresponding view. For example, to use 
a different view for `404` errors, you can simply create a `views/errors/404.blade.php` file and it will be used 
automatically.

### Maintenance Mode

By default, Laravel [uses a custom view](https://laravel.com/docs/5.2/configuration#maintenance-mode) in maintenance mode
to make it easy to disable your application while it's updating. The default template for maintenance mode responses is 
located in `views/errors/503.blade.php`. If you remove that file, this package still has you covered: Laravel's 
maintenance mode simply throws an exception with a `503` HTTP status code, so you will be shown the default error view 
when your application's debug mode is off. With debug mode turned on, you will be shown the actual exception with the 
Whoops error handler.

## Events

Whenever an error occurs, an `ExceptionEvent` is raised. You can use your `EventServiceProvider` to listen to this type
of event if you wish:


```php
    protected $listen = [
        'Winternight\LaravelErrorHandler\Events\ExceptionEvent' => [
            'App\Listeners\ExceptionEventListener',
        ],
    ];
```

## Is this compatible with [Laravel Debug Bar](https://github.com/barryvdh/laravel-debugbar "barryvdh/laravel-debugbar")?

I'm glad you asked! Yes, it is. The debug bar simply renders on top of the Whoops error page. You can also use the Event
that is being fired whenever an exception occurs to add it to your debug bar. Here's a small example which you can put
in your `EventServiceProvider`'s `boot()` method:

```php
\Event::listen( 'Winternight\LaravelErrorHandler\Events\ExceptionEvent', function ( $event ) {
    \Debugbar::addException( $event->getException() );
} );
```

## Troubleshooting

If you're not seeing any difference (and you have made sure there actually is an error being thrown, you probably have 
debugging disabled. That means that Laravel will (and should) not disclose any details regarding the error that has 
occurred. It's for your own protection.

To enable Whoops, open up your `config/app.php` configuration file, find the `debug` setting and change it to `true`. As
soon as you encounter an error, you will see the Whoops error handler. If you have done everything right, you should
probably use the `.env` file to set the `APP_DEBUG` environment variable.

## What about AJAX requests?

Whenever an AJAX request triggers the error handler, it will be recognized and the so called `PrettyPageHandler` will be
exchanged for a `JsonResponseHandler` that returns a JSON response that you can parse on the client side. You can read 
[more here](https://github.com/filp/whoops/blob/master/docs/API%20Documentation.md#-whoopshandlerjsonresponsehandler).
If debug mode is turned off, a different JSON object will be returned instead that still allows you to gracefully handle
your AJAX errors, whle not giving out any information about your code.

Here is a (very) simple jQuery snippet for global AJAX error handling:

```js
$( document ).ajaxError(function( evt, xhr ) {
    console.log( xhr.responseJSON.error );
} );
```

The JSON object will always contain an `error` property which in turn will always at least contain the `type` and `message`
properties. If debug mode is enabled, it will additionally contain the `file` and `line` properties and -- in case of an 
Exception -- also the `trace` property. The `trace` property contains the full exception stack trace.

## What about errors in the console?

The package is smart enough not to mess with output to the console. Enjoy your errors in plain text!
