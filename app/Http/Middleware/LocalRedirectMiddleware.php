<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class LocalRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $replace = 'localhost';
        $host = $request->headers->get('host');

        if (strcmp($host, $replace))
            return $next($request);

        $url = URL::full();
        return redirect(
            str($url)->replace("http://$replace", config('app.url'))
        );
    }
}
