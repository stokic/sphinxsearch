<?php namespace MeridianStudio\SphinxSearch;

use Illuminate\Support\ServiceProvider;

class SphinxSearchServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([__DIR__.'/../../config/config.php' => config_path('sphinxsearch.php'),], 'config');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->singleton('sphinxsearch', function()
        {
            return new SphinxSearch;
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('sphinxsearch');
	}

}
