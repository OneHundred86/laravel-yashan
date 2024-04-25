<?php

namespace Oh86\LaravelYashan;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use Oh86\LaravelYashan\Connectors\YSConnector as Connector;

class YSServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot Oci8 Provider.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/yashan.php' => config_path('yashan.php'),
        ], 'dm');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (file_exists(config_path('yashan.php'))) {
            $this->mergeConfigFrom(config_path('yashan.php'), 'database.connections');
        } else {
            $this->mergeConfigFrom(__DIR__.'/../config/yashan.php', 'database.connections');
        }

        Connection::resolverFor('yashan', function ($pdo, $database, $prefix, $config) {
            $connector = new Connector();
            $pdo = $connector->connect($config);
            return new YSConnection($pdo, $database, $prefix, $config);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [];
    }
}
