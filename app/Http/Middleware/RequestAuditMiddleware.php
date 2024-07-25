<?php

namespace App\Http\Middleware;

use App\Models\TextFilterAudit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestAuditMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $textFilterAudit=TextFilterAudit::query()->create([
            'user_id' => $request->user()->id,
            'request_body' => json_encode($request->all(),JSON_PRETTY_PRINT)
        ]);

        $request->merge([
            'text_filter_audit_id' => $textFilterAudit->id
        ]);

        return $next($request);
    }
}
