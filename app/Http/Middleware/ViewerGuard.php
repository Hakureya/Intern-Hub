<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Akun role viewer hanya boleh membuka halaman log pekerjaan (viewer.*),
 * mengunduh lampiran, dan logout. Rute lain dialihkan.
 */
class ViewerGuard
{
    private const ALLOWED = [
        'work-logs.file.attachment',
        'work-logs.file.reference',
        'logout',
    ];

    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->isViewer()) {
            $name = (string) optional($request->route())->getName();

            if (strpos($name, 'viewer.') !== 0 && ! in_array($name, self::ALLOWED, true)) {
                return redirect()->route('viewer.work-logs.index');
            }
        }

        return $next($request);
    }
}
