<?php namespace Winternight\LaravelErrorHandler\Contracts;

use Exception;

use Illuminate\Http\Request;


/**
 * Interface DisplayContract.
 *
 * @package Winternight\LaravelErrorHandler\Contracts
 */
interface DisplayContract {

	/**
	 * Get the HTML content associated with the given exception.
	 *
	 * @param \Exception $exception
	 * @param int        $code
	 *
	 * @return string|array
	 */
	public function display( Exception $exception, $code );


	/**
	 * Sets the Request instance on the display class.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return self
	 */
	public function setRequest( Request $request );
}
