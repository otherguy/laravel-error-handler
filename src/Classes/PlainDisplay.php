<?php namespace Winternight\LaravelErrorHandler\Classes;

use Exception;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Translation\Translator as Lang;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as View;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use Winternight\LaravelErrorHandler\Contracts\DisplayContract;

/**
 * Class PlainDisplay.
 *
 * @package Winternight\LaravelErrorHandler\Classes
 */
class PlainDisplay implements DisplayContract
{
    /** @var \Illuminate\Contracts\Config\Repository */
    protected $config;

    /** @var \Illuminate\Http\Request */
    protected $request;

    /** @var \Illuminate\Translation\Translator */
    protected $lang;

    /** @var \Illuminate\Contracts\View\Factory */
    protected $view;

    /**
     * Construct.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Illuminate\Contracts\View\Factory      $view
     * @param \Illuminate\Translation\Translator      $lang
     */
    public function __construct(Config $config, View $view, Lang $lang)
    {
        $this->config = $config;
        $this->view   = $view;
        $this->lang   = $lang;
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
     * Get the HTML content associated with the given exception.
     *
     * @param \Exception $exception
     * @param int        $code
     *
     * @return string
     */
    public function display(Exception $exception, $code)
    {
        // Collect some info about the exception.
        $info = $this->info($code, $exception);

        // Is the current request an AJAX request?
        if ((boolean)($this->request instanceof Request && $this->request->wantsJson()) == true) {
            $json_object = (object)[ 'error' => [
                'type'    => 'Exception',
                'message' => $info['message'],
            ] ];

            return JsonResponse::create($json_object, $code);
        }

        // For model-not-found, use 404 errors.
        if ($exception instanceof ModelNotFoundException) {
            $code = 404;
        }

        // If there is a custom view, use that.
        if ($this->view->exists("errors.{$code}")) {
            return $this->view->make("errors.{$code}", $info)->render();
        }

        // If the configured default error view (or errors.error) exists, render that.
        if (($errorview = $this->config->get('view.error', 'errors.error')) && $this->view->exists($errorview)) {
            return $this->view->make($errorview, $info)->render();
        }

        // Last resort: simply show the error code and message.
        return new Response($this->getFallbackDisplay($code, $info['name'], $info['message']), $code);
    }

    /**
     * Get the exception information.
     *
     * @param int        $code
     * @param \Exception $exception
     *
     * @return array
     */
    protected function info($code, Exception $exception)
    {
        $description = $exception->getMessage();
        $namespace   = 'winternight/laravel-error-handler';

        // If there is no error message for the given HTTP code, default to 500.
        if (!$this->lang->has("$namespace::messages.error.$code.name")) {
            $code = 500;
        }

        $name    = $this->lang->get("$namespace::messages.error.$code.name");
        $message = $this->lang->get("$namespace::messages.error.$code.message");

        return compact('code', 'name', 'message', 'description');
    }

    /**
     * Get the fallback html response content.
     *
     * @param string $code
     * @param string $name
     * @param string $message
     *
     * @return string
     */
    protected function getFallbackDisplay($code, $name, $message)
    {
        return <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <meta name="robots" content="noindex,nofollow">
    </head>
    <body>
        <h1>$code $name</h1>
        <p>$message</p>
    </body>
</html>
EOF;
    }
}
