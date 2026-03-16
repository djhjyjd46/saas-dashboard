<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // For now, we rely on the TenantManager getting the tenant from Auth
        // In the future, we can add domain-based detection here.

        return $next($request);
    }
}
