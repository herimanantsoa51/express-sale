<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Enums\ActivityAction;

class AuthController extends Controller
{
    /**
     * Création d'un utilisateur
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

        // ✅ Log la création (l'utilisateur courant crée un autre utilisateur)
        if ($currentUser = $request->user()) {
            ActivityLog::create([
                'user_id' => $currentUser->id,
                'action' => ActivityAction::USER_CREATED,
                'status' => 'success',
                'model_type' => 'App\Models\User',
                'model_id' => $user->id,
                'description' => "a créé l'utilisateur {$user->name} ({$user->username}) avec le rôle {$user->role}",
                'metadata' => [
                    'username' => $user->username,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                ],
                'frontend_path' => "users/{$user->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

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
            // On ne peut pas utiliser ActivityLogger car pas d'utilisateur authentifié
            ActivityLog::create([
                'user_id' => null, // Pas d'utilisateur
                'action' => ActivityAction::LOGIN_FAILED,
                'status' => 'failed',
                'description' => "Tentative de connexion avec username inexistant: {$request->username}",
                'metadata' => [
                    'username' => $request->username,
                    'reason' => 'user_not_found',
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            throw ValidationException::withMessages([
                'username' => ['Identifiants incorrects'],
            ]);
        }
        
        // Vérifier si le compte est actif
        if (!$user->is_active) {
            // ⚠️ Log tentative de connexion sur compte désactivé
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => ActivityAction::LOGIN_FAILED,
                'status' => 'failed',
                'model_type' => 'App\Models\User',
                'model_id' => $user->id,
                'description' => "{$user->name} a tenté de se connecter avec un compte désactivé",
                'metadata' => [
                    'username' => $user->username,
                    'reason' => 'account_disabled',
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            return response()->json([
                'message' => 'Compte désactivé'
            ], 403);
        }
        
        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            // ⚠️ Log tentative avec mauvais mot de passe
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => ActivityAction::LOGIN_FAILED,
                'status' => 'failed',
                'model_type' => 'App\Models\User',
                'model_id' => $user->id,
                'description' => "{$user->name} a tenté de se connecter avec un mot de passe incorrect",
                'metadata' => [
                    'username' => $user->username,
                    'reason' => 'wrong_password',
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            throw ValidationException::withMessages([
                'username' => ['Identifiants incorrects'],
            ]);
        }

        // Supprimer anciens tokens (optionnel mais propre)
        $user->tokens()->delete();

        $token = $user->createToken('express-sale-token')->plainTextToken;

        // ✅ Log connexion réussie
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => ActivityAction::LOGIN_SUCCESS,
            'status' => 'success',
            'model_type' => 'App\Models\User',
            'model_id' => $user->id,
            'description' => "s'est connecté avec succès",
            'metadata' => [
                'username' => $user->username,
                'role' => $user->role,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

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
        
        // ✅ Log AVANT de supprimer le token
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => ActivityAction::LOGOUT,
            'status' => 'success',
            'model_type' => 'App\Models\User',
            'model_id' => $user->id,
            'description' => "s'est déconnecté",
            'metadata' => [
                'username' => $user->username,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }
}