<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Création d'un utilisateur (optionnel pour admin / setup)
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:4',
            'role'=> 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'role' => $request->role ?? 'vendeur',
            'password' => Hash::make($request->password),
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé',
            'user' => $user,
        ], 201);
    }

    /**
     * Login API → retourne un token Sanctum
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Compte désactivé'
            ], 403);
        }
        
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Identifiants incorrects'],
            ]);
        }

        // Supprimer anciens tokens (optionnel mais propre)
        $user->tokens()->delete();

        $token = $user->createToken('express-sale-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Infos utilisateur connecté
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Logout → supprime le token courant
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }
}
