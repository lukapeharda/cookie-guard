# Cookie Guard

## Introduction

Cookie Guard is a Laravel API authentication package using cookie tokens. Most of its inner workings are taken from Laravel Passport package.

## Installation

Require this package, with [Composer](https://getcomposer.org/), in the root directory of your project.

```bash
$ composer require lukapeharda/cookie-guard
```

Add the service provider to `config/app.php` in the `providers` array.

```php
LukaPeharda\CookieGuard\CookieGuardServiceProvider::class,
```

Add the `LukaPeharda\CookieGuard\HasApiTokens` trait to your `App\User` model. This trait will provide a few helper methods to your model which allow you to inspect the authenticated user's token and scopes:

```php
<?php

namespace App;

use LukaPeharda\CookieGuard\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
}
```

Add the `CreateFreshApiToken` middleware to your `web` middleware group:
```php
'web' => [
    // Other middleware...
    \LukaPeharda\CookieGuard\Http\Middleware\CreateFreshApiToken::class,
],
```

This middleware will attach a `laravel_token` cookie to your outgoing responses.

Finally, in your `config/auth.php` configuration file, you should set the `driver` option of the `api` authentication guard to `cookie`. This will instruct your application to use CookieGuards's `CookieGuard` when authenticating incoming API requests:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'cookie',
        'provider' => 'users',
    ],
],
```


## License

Cookie Guard is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
