<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIsUserActiveMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()->is_active) {
            return response()->json([
                'status' => 'failed',
                'timestamp' => Carbon::now()->timestamp,
                'error_message' => $request->user()->user_inactivity_message
            ], 500);
        }

        return $next($request);
    }
}
