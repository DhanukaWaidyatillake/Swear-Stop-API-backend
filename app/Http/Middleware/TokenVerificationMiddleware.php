<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Services\TokenManagerService;
use Carbon\Carbon;
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
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer_token = $request->bearerToken();

        if (empty($bearer_token) || trim($bearer_token) == "") {
            return response()->json([
                'status' => 'failed',
                'timestamp' => Carbon::now()->timestamp,
                'error_message' => 'API key cannot be empty'
            ], 401);
        }

        $encrypted_token = $this->tokenManagerService->encrypt($bearer_token);
        $api_token = ApiToken::query()->where('encrypted_api_key', $encrypted_token)->first();
        if ($api_token) {
            $request->setUserResolver(function () use ($api_token) {
                return $api_token->user;
            });
        } else {
            return response()->json([
                'status' => 'failed',
                'timestamp' => Carbon::now()->timestamp,
                'error_message' => 'Invalid API key'
            ], 401);
        }
        return $next($request);
    }
}
