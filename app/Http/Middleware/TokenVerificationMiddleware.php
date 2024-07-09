<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Services\TokenManagerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenVerificationMiddleware
{
    protected TokenManagerService $tokenManagerService;

    public function __construct(TokenManagerService $tokenManagerService)
    {
        $this->tokenManagerService = $tokenManagerService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $encrypted_token=$this->tokenManagerService->encrypt($request->bearerToken());
        $api_token=ApiToken::query()->where('encrypted_api_key',$encrypted_token)->first();
        if($api_token) {
            $request->setUserResolver(function () use ($api_token) {
                return $api_token->user;
            });
        } else {
            abort(401);
        }
        return $next($request);
    }
}
