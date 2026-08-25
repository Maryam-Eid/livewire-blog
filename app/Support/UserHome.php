<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class UserHome
{
    public function path(User $user): string
    {
        return $user->can('create-post')
            ? route('dashboard', absolute: false)
            : route('blog.index', absolute: false);
    }

    public function redirect(Request $request, string $query = ''): RedirectResponse
    {
        $fallback = $this->path($request->user());
        $intended = $request->session()->pull('url.intended', $fallback);

        if (! $this->userCanAccess($request->user(), $intended)) {
            $intended = $fallback;
        }

        if ($query !== '') {
            $intended .= (str_contains($intended, '?') ? '&' : '?').ltrim($query, '?&');
        }

        return redirect()->to($intended);
    }

    private function userCanAccess(User $user, string $intended): bool
    {
        $path = parse_url($intended, PHP_URL_PATH) ?: $intended;

        try {
            $route = app('router')->getRoutes()->match(Request::create($path));
        } catch (Throwable) {
            return true;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (! str_starts_with($middleware, 'can:')) {
                continue;
            }

            if (! $user->can(substr($middleware, 4))) {
                return false;
            }
        }

        return true;
    }
}
