<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    /**
     * Selain memastikan user login, tolak akun yang sudah dinonaktifkan
     * (termasuk sesi yang masih berjalan saat akun dinonaktifkan).
     */
    protected function authenticate($request, array $guards)
    {
        parent::authenticate($request, $guards);

        $user = $request->user();

        if ($user && (int) ($user->is_active ?? 1) === 0) {
            $this->auth->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new HttpResponseException(
                redirect()->route('login')->withErrors([
                    'email' => 'Akun Anda dinonaktifkan. Silakan hubungi bagian IT.',
                ])
            );
        }
    }
}
