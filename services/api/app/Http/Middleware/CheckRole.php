<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (! $request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Non authentifié',
            ], 401);
        }

        // Vérifier le rôle
        if ($request->user()->role !== $role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès non autorisé. Rôle requis: '.$role,
            ], 403);
        }

        return $next($request);
    }
}
