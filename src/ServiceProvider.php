<?php namespace Winternight\LaravelErrorHandler;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Class ServiceProvider.
 *
 * @package Winternight\LaravelErrorHandler
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var boolean
     */
    protected $defer = false;

    /**
     * The namespace of the loaded config files.
     *
     * @var string
     */
    public $namespace = 'winternight/laravel-error-handler';

    /**
     * Registers resources for the package.
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../../resources/lang/', $this->namespace);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Illuminate\Contracts\Debug\ExceptionHandler'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
