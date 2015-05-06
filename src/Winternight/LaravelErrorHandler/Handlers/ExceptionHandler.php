<?php namespace Winternight\LaravelErrorHandler\Handlers;

use Exception;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler as BaseExceptionHandler;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\View;

use Psr\Log\LoggerInterface;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

use Winternight\LaravelErrorHandler\Classes\PlainDisplay;
use Winternight\LaravelErrorHandler\Classes\DebugDisplay;
use Winternight\LaravelErrorHandler\Events\ExceptionEvent;

/**
 * Class ExceptionHandler.
 *
 * @package Winternight\LaravelErrorHandler\Handlers
 */
class ExceptionHandler extends BaseExceptionHandler {

	/**
	 * @var \Illuminate\Contracts\Config\Repository
	 */
	protected $config;

	/**
	 * @var \Illuminate\Support\Facades\App
	 */
	protected $app;

	/**
	 * A list of the exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $dontReport = [
		'Symfony\Component\HttpKernel\Exception\HttpException'
	];


	/**
	 * Create a new exception handler instance.
	 *
	 * @param \Psr\Log\LoggerInterface                     $log
	 * @param \Illuminate\Contracts\Foundation\Application $app
	 * @param \Illuminate\Contracts\Config\Repository      $config
	 */
	public function __construct( LoggerInterface $log, Application $app, Config $config ) {
		$this->config = $config;
		$this->app    = $app;

		parent::__construct( $log );
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
	public function report( Exception $e ) {
		event( new ExceptionEvent( $e ) );

		return parent::report( $e );
	}

	/**
	 * Render an exception into a response.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Exception               $exception
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function render( $request, Exception $exception ) {
		$flattened = FlattenException::create( $exception );

		$code     = $flattened->getStatusCode();
		$headers  = $flattened->getHeaders();
		$response = $this->getContent( $exception, $code, $request );

		// If it's already a response, return that.
		if( $response instanceof BaseResponse ) {
			return $response;
		}

		return new Response( $this->getContent( $exception, $code, $request ), $code, $headers );
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
	protected function getContent( Exception $exception, $code, Request $request ) {

		// In debug mode, use the DebugDisplay class.
		if( (boolean)$this->config->get( 'app.debug' ) === true ) {
			return $this->app->make( DebugDisplay::class )->setRequest( $request )->display( $exception, $code );
		}

		// For production/non-debug environments, use the PlainDisplay class.
		return $this->app->make( PlainDisplay::class )->setRequest( $request )->display( $exception, $code );
	}
}
