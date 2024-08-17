<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class XenditCallbackToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('x-callback-token') != env('XENDIT_CALLBACK_TOKEN')) {
            return response([
                'status' => 'failed',
                'message' => 'Xendit callback token not valid',
            ], 401);
        }
        return $next($request);
    }
}