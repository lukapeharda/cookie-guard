<?php

namespace LukaPeharda\CookieGuard;

use DateInterval;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use LukaPeharda\CookieGuard\Guards\CookieGuard;
use Illuminate\Support\ServiceProvider;

class CookieGuardServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerGuard();
    }

    /**
     * Register the token guard.
     *
     * @return void
     */
    protected function registerGuard()
    {
        Auth::extend('cookie', function ($app, $name, array $config) {
            return tap($this->makeGuard($config), function ($guard) {
                $this->app->refresh('request', $guard, 'setRequest');
            });
        });
    }

    /**
     * Make an instance of the token guard.
     *
     * @param  array  $config
     * @return RequestGuard
     */
    protected function makeGuard(array $config)
    {
        return new RequestGuard(function ($request) use ($config) {
            return (new CookieGuard(
                Auth::createUserProvider($config['provider']),
                $this->app->make('encrypter')
            ))->user($request);
        }, $this->app['request']);
    }
}
