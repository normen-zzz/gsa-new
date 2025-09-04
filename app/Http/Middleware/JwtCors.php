<?php
// app/Http/Middleware/JwtCors.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JwtCors
{
    public function handle(Request $request, Closure $next)
    {
        // Handle preflight OPTIONS request sebelum JWT middleware
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', 'https://superapps-gsa.vercel.app')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, Cache-Control, X-Auth-Token')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Add CORS headers ke response
        if (method_exists($response, 'header')) {
            return $response
                ->header('Access-Control-Allow-Origin', 'https://superapps-gsa.vercel.app')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, Cache-Control, X-Auth-Token')
                ->header('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}