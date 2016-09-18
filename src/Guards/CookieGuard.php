<?php

namespace LukaPeharda\CookieGuard\Guards;

use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use LukaPeharda\CookieGuard\TransientToken;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Debug\ExceptionHandler;

class CookieGuard
{
    /**
     * The user provider implementation.
     *
     * @var UserProvider
     */
    protected $provider;

    /**
     * The encrypter implementation.
     *
     * @var Encrypter
     */
    protected $encrypter;

    /**
     * Create a new token guard instance.
     *
     * @param  UserProvider  $provider
     * @param  Encrypter  $encrypter
     * @return void
     */
    public function __construct(UserProvider $provider,
                                Encrypter $encrypter)
    {
        $this->provider = $provider;
        $this->encrypter = $encrypter;
    }

    /**
     * Get the user for the incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function user(Request $request)
    {
        if ($request->cookie('laravel_token')) {
            return $this->authenticateViaCookie($request);
        }
    }

    /**
     * Authenticate the incoming request via the token cookie.
     *
     * @param  Request  $request
     * @return mixed
     */
    protected function authenticateViaCookie($request)
    {
        // If we need to retrieve the token from the cookie, it'll be encrypted so we must
        // first decrypt the cookie and then attempt to find the token value within the
        // database. If we can't decrypt the value we'll bail out with a null return.
        try {
            $token = $this->decodeJwtTokenCookie($request);
        } catch (Exception $e) {
            return;
        }

        // We will compare the CSRF token in the decoded API token against the CSRF header
        // sent with the request. If the two don't match then this request is sent from
        // a valid source and we won't authenticate the request for further handling.
        if (! $this->validCsrf($token, $request) ||
            time() >= $token['expiry']) {
            return;
        }

        // If this user exists, we will return this user and attach a "transient" token to
        // the user model. The transient token assumes it has all scopes since the user
        // is physically logged into the application via the application's interface.
        if ($user = $this->provider->retrieveById($token['sub'])) {
            return $user->withAccessToken(new TransientToken);
        }
    }

    /**
     * Decode and decrypt the JWT token cookie.
     *
     * @param  Request  $request
     * @return array
     */
    protected function decodeJwtTokenCookie($request)
    {
        return (array) JWT::decode(
            $this->encrypter->decrypt($request->cookie('laravel_token')),
            $this->encrypter->getKey(), ['HS256']
        );
    }

    /**
     * Determine if the CSRF / header are valid and match.
     *
     * @param  array  $token
     * @param  Request  $request
     * @return bool
     */
    protected function validCsrf($token, $request)
    {
        return isset($token['csrf']) && hash_equals(
            $token['csrf'], (string) $request->header('X-CSRF-TOKEN')
        );
    }
}
