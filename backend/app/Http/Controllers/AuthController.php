<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Helpers\ActivityLogger;
use App\Helpers\FrontendRoutes;
use App\Enums\ActivityAction;

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

        // ✅ Log la création de l'utilisateur
        ActivityLogger::success(
            ActivityAction::USER_CREATED,
            "a créé l'utilisateur {$user->name} ({$user->username}) avec le rôle {$user->role}",
            [
                'model_type' => 'App\Models\User',
                'model_id' => $user->id,
                'metadata' => [
                    'username' => $user->username,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                ]
            ],
            FrontendRoutes::user($user->id),
            [
                'all_users' => [
                    'label' => 'Tous les utilisateurs',
                    'path' => '/users',
                ],
            ]
        );

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
        
        // Vérifier si l'utilisateur existe
        if (!$user) {
            // ⚠️ Log tentative de connexion avec username inexistant
            ActivityLogger::failed(
                'login_failed',
                "a tenté de se connecter avec un username inexistant: {$request->username}",
                [
                    'metadata' => [
                        'username' => $request->username,
                        'reason' => 'user_not_found',
                    ]
                ]
            );
            
            throw ValidationException::withMessages([
                'username' => ['Identifiants incorrects'],
            ]);
        }
        
        // Vérifier si le compte est actif
        if (!$user->is_active) {
            // ⚠️ Log tentative de connexion sur compte désactivé
            ActivityLogger::failed(
                'login_failed',
                "a tenté de se connecter avec un compte désactivé: {$user->username}",
                [
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                    'metadata' => [
                        'username' => $user->username,
                        'reason' => 'account_disabled',
                    ]
                ]
            );
            
            return response()->json([
                'message' => 'Compte désactivé'
            ], 403);
        }
        
        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            // ⚠️ Log tentative avec mauvais mot de passe
            ActivityLogger::failed(
                'login_failed',
                "a tenté de se connecter avec un mot de passe incorrect: {$user->username}",
                [
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                    'metadata' => [
                        'username' => $user->username,
                        'reason' => 'wrong_password',
                    ]
                ]
            );
            
            throw ValidationException::withMessages([
                'username' => ['Identifiants incorrects'],
            ]);
        }

        // Supprimer anciens tokens (optionnel mais propre)
        $user->tokens()->delete();

        $token = $user->createToken('express-sale-token')->plainTextToken;

        // ✅ Log connexion réussie
        ActivityLogger::success(
            'login_success',
            "s'est connecté avec succès",
            [
                'model_type' => 'App\Models\User',
                'model_id' => $user->id,
                'metadata' => [
                    'username' => $user->username,
                    'role' => $user->role,
                ]
            ]
        );

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
        $user = $request->user();
        
        $request->user()->currentAccessToken()->delete();

        // ✅ Log déconnexion
        ActivityLogger::success(
            'logout',
            "s'est déconnecté",
            [
                'model_type' => 'App\Models\User',
                'model_id' => $user->id,
                'metadata' => [
                    'username' => $user->username,
                ]
            ]
        );

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }
}