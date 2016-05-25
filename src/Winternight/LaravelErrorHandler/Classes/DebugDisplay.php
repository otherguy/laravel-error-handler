<?php namespace Winternight\LaravelErrorHandler\Classes;

use Exception;

use Illuminate\Http\Request;

use Whoops\Handler\PrettyPageHandler as WhoopsHtmlHandler;
use Whoops\Handler\JsonResponseHandler as WhoopsJsonHandler;
use Whoops\Handler\XmlResponseHandler as WhoopsXmlHandler;

use Whoops\Run as Whoops;

use Winternight\LaravelErrorHandler\Contracts\DisplayContract;

/**
 * Class DebugDisplay.
 *
 * @package Winternight\LaravelErrorHandler\Classes
 */
class DebugDisplay implements DisplayContract
{
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
    public function display(Exception $exception, $code)
    {
        return $this->whoops()->handleException($exception);
    }

    /**
     * Set the HTTP Request instance.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the whoops instance.
     *
     * @return \Whoops\Run
     */
    protected function whoops()
    {
        // Default to the Whoops HTML Handler
        $handler = new WhoopsHtmlHandler();

        // For JSON or XML Requests, return the proper output too.
        if ($this->request instanceof Request) {
            if ($this->request->wantsJson()) {
                $handler = new WhoopsJsonHandler();
                $handler->addTraceToOutput(true);
            } elseif ($this->request->getContentType() == 'xml') {
                $handler = new WhoopsXmlHandler();
                $handler->addTraceToOutput(true);
            }
        }

        // Build Whoops.
        $whoops = new Whoops();
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->pushHandler($handler);
        $whoops->register();

        // Return Whoops.
        return $whoops;
    }
}
