<?php namespace Winternight\LaravelErrorHandler\Handlers;

use Exception;

use Illuminate\Foundation\Exceptions\Handler as BaseExceptionHandler;

use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Winternight\LaravelErrorHandler\Classes\PlainDisplay;
use Winternight\LaravelErrorHandler\Classes\DebugDisplay;
use Winternight\LaravelErrorHandler\Events\ExceptionEvent;

use Illuminate\Contracts\Container\Container;

use Whoops\Run as Whoops;

/**
 * Class ExceptionHandler.
 *
 * @package Winternight\LaravelErrorHandler\Handlers
 */
class ExceptionHandler extends BaseExceptionHandler
{
    /** @var \Illuminate\Contracts\Config\Repository */
    protected $config;

    /** @var \Illuminate\Support\Facades\App */
    protected $app;

    /** @var \Illuminate\Contracts\Events\Dispatcher */
    protected $event;

    /** @var \Illuminate\Contracts\Container\Container */
    protected $container;

    /**
     * Create a new exception handler instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     */
    public function __construct(Container $container)
    {
        $this->config    = $container->config;
        $this->app       = $container->app;
        $this->event     = $container->events;
        $this->container = $container;

        parent::__construct($container);
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     * In this implementation, we just send an Event though.
     *
     * @param  \Exception $e
     *
     * @return void
     */
    public function report(Exception $e)
    {
        if ($this->shouldReport($e)) {
            $this->event->fire(new ExceptionEvent($e));
        }
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof HttpResponseException) {
            return $exception->getResponse();
        } elseif ($exception instanceof ModelNotFoundException) {
            $exception = new NotFoundHttpException($exception->getMessage(), $exception);
        } elseif ($exception instanceof AuthorizationException) {
            $exception = new HttpException(403, $exception->getMessage());
        } elseif ($exception instanceof ValidationException && $exception->getResponse()) {
            return $exception->getResponse();
        }

        $flattened = FlattenException::create($exception);

        $code     = $flattened->getStatusCode();
        $headers  = $flattened->getHeaders();
        $response = $this->getContent($exception, $code, $request);

        // If it's already a response, return that.
        if (!$response instanceof BaseResponse) {
            $response = new Response($this->getContent($exception, $code, $request), $code, $headers);
        }

        return $response;
    }

    /**
     * Get the HTML content associated with the given exception.
     *
     * @param \Exception               $exception
     * @param int                      $code
     * @param \Illuminate\Http\Request $request
     *
     * @return string
     */
    protected function getContent(Exception $exception, $code, Request $request)
    {

        // Only if the debug mode is enabled, show a more verbose error message.
        if ((boolean)$this->config->get('app.debug') === true) {
            if (class_exists(Whoops::class)) {
                // If Whoops is loaded, use the DebugDisplay class.
                return $this->app->make(DebugDisplay::class)->setRequest($request)->display($exception, $code);
            } else {
                // Fall back to the default Laravel error response.
                return $this->toIlluminateResponse($this->convertExceptionToResponse($exception), $exception);
            }
        }

        // For production/non-debug environments, use the PlainDisplay class.
        return $this->app->make(PlainDisplay::class)->setRequest($request)->display($exception, $code);
    }
    
    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}
