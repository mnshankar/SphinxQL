<?php namespace mnshankar\Sphinxql;

use Illuminate\Support\ServiceProvider;

class SphinxqlServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;
	public function boot()
	{
	    $this->package('mnshankar/sphinxql');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['sphinxql'] = $this->app->share(function($app)
		{
		    $host = \Config::get('sphinxql::host');
		    $port = \Config::get('sphinxql::port');
		    $connection = new \Foolz\SphinxQL\Connection();
		    $connection->setConnectionParams($host, $port);		    
		    return new Sphinxql(new \Foolz\SphinxQL\SphinxQL($connection));		    
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('sphinxql');
	}

}