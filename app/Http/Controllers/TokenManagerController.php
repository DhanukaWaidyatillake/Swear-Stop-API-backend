<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateTokenRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Models\User;
use App\Services\TokenManagerService;

class TokenManagerController extends Controller
{
    public function generateToken(GenerateTokenRequest $request,TokenManagerService $tokenManagerService): \Illuminate\Http\JsonResponse
    {
        $user = User::query()->find($request->user_id);

        if ($user && $user->createToken($tokenManagerService)) {
            return response()->json('success', 200);
        } else {
            return response()->json('failed to create token', 500);
        }
    }

    public function refreshToken(RefreshTokenRequest $request,TokenManagerService $tokenManagerService)
    {
        $user = User::query()->find($request->user_id);
        $user->apiKeys()->delete();

        if ($user && $user->createToken($tokenManagerService)) {
            return response('success', 200);
        } else {
            return response('failed to create token', 500);
        }
    }
}
