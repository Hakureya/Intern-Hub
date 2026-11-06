<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->user() === null) {
            return redirect()->route('login');
        }

        $actions = $request->route()->getAction();
        $roles = isset($actions['roles']) ? $actions['roles'] : null;

        if ($roles !== null && auth()->user()->hasAnyRole($roles)) {
            return $next($request);
        }

        if (auth()->user()->hasRole('user')) {
            return redirect()->route('home')->with('error', 'Akses admin tidak tersedia untuk akun user.');
        }

        if (auth()->user()->isMentor()) {
            return redirect()->route('admin')->with('error', 'Akses tidak tersedia untuk akun mentor.');
        }

        abort(403, 'Unauthorized action.');
    }
}
