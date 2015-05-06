<?php namespace Winternight\LaravelErrorHandler\Classes;

use Exception;

use Illuminate\Http\Request;

use Whoops\Handler\PrettyPageHandler as WhoopsHtmlHandler;
use Whoops\Handler\JsonResponseHandler as WhoopsJsonHandler;

use Whoops\Run as Whoops;

use Winternight\LaravelErrorHandler\Contracts\DisplayContract;

/**
 * Class DebugDisplay.
 *
 * @package Winternight\LaravelErrorHandler\Classes
 */
class DebugDisplay implements DisplayContract {

	/**
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * Get the Whoops HTML content associated with the given exception.
	 *
	 * @param \Exception $exception
	 * @param int        $code
	 *
	 * @return string
	 */
	public function display( Exception $exception, $code ) {
		return $this->whoops()->handleException( $exception );
	}

	/**
	 * Set the HTTP Request instance.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return $this
	 */
	public function setRequest( Request $request ) {
		$this->request = $request;
		return $this;
	}

	/**
	 * Get the whoops instance.
	 *
	 * @return \Whoops\Run
	 */
	protected function whoops() {
		// Is the current request an AJAX request?
		$isAjaxRequest = (boolean)( $this->request instanceof Request && $this->request->ajax() );

		// Build Whoops.
		$whoops = new Whoops();
		$whoops->allowQuit( false );
		$whoops->writeToOutput( false );
		$whoops->pushHandler( $isAjaxRequest ? ( new WhoopsJsonHandler() )->addTraceToOutput( true ) : new WhoopsHtmlHandler() );

		// Return Whoops.
		return $whoops;
	}
}
