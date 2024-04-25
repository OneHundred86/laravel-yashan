<?php

namespace Oh86\LaravelYashan;

use Illuminate\Validation\ValidationServiceProvider;
use Oh86\LaravelYashan\Validation\YSDatabasePresenceVerifier;

class YSValidationServiceProvider extends ValidationServiceProvider
{
    protected function registerPresenceVerifier()
    {
        $this->app->singleton('validation.presence', function ($app) {
            return new YSDatabasePresenceVerifier($app['db']);
        });
    }
}
