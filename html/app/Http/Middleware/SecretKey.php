<?php

namespace App\Http\Middleware;

use Closure;

class SecretKey
{
    const SECRET_KEY = 'l2ggh1imVxJ7Oj6BotPUWinAIDgJfBYw';
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->get('secretKey') != self::SECRET_KEY) return response()->json('Invalid Secret Key');
        return $next($request);
    }
}
