<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Helpers\ActivityLogger;
use App\Helpers\FrontendRoutes;
use App\Enums\ActivityAction;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('is_active', true)
                    ->select(['id', 'name'])
                    ->get();
        return response()->json($users);
    }

    /**
     * Liste des utilisateurs GET /users/all
     */
    public function indexUsers()
    {
        return response()->json(
            User::select('id', 'name', 'username', 'role', 'is_active', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    /**
     * Créer un utilisateur POST /users
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'username' => 'required|string|unique:users,username',
                'password' => 'required|string|min:4',
                'role' => 'required|in:admin,vendeur',
                'is_active' => 'sometimes|boolean',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            // ✅ Log création utilisateur
            ActivityLogger::success(
                ActivityAction::USER_CREATED,
                "a créé l'utilisateur {$user->name} ({$user->username}) - Rôle: {$user->role}",
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

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Modifier nom / rôle / activation PUT /users/{id}
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);

            // Sauvegarder les anciennes valeurs pour comparaison
            $oldValues = [
                'name' => $user->name,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ];

            $validated = $request->validate([
                'name' => 'sometimes|string',
                'role' => 'sometimes|in:admin,vendeur',
                'is_active' => 'sometimes|boolean',
                'password' => 'sometimes|string|min:4',
            ]);

            $passwordChanged = false;
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
                $passwordChanged = true;
            }

            $user->update($validated);

            // Construire le message de description
            $changes = [];
            if (isset($validated['name']) && $validated['name'] !== $oldValues['name']) {
                $changes[] = "nom: '{$oldValues['name']}' → '{$validated['name']}'";
            }
            if (isset($validated['role']) && $validated['role'] !== $oldValues['role']) {
                $changes[] = "rôle: '{$oldValues['role']}' → '{$validated['role']}'";
            }
            if (isset($validated['is_active']) && $validated['is_active'] !== $oldValues['is_active']) {
                $status = $validated['is_active'] ? 'activé' : 'désactivé';
                $changes[] = "statut: {$status}";
            }
            if ($passwordChanged) {
                $changes[] = "mot de passe modifié";
            }

            $description = count($changes) > 0 
                ? "a modifié l'utilisateur {$user->name} - " . implode(', ', $changes)
                : "a modifié l'utilisateur {$user->name}";

            DB::commit();

            // ✅ Log modification utilisateur
            ActivityLogger::success(
                ActivityAction::USER_UPDATED,
                $description,
                [
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                    'metadata' => [
                        'old_values' => $oldValues,
                        'new_values' => $user->only(['name', 'role', 'is_active']),
                        'password_changed' => $passwordChanged,
                        'changes' => $changes,
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
                'message' => 'Utilisateur mis à jour',
                'user' => $user,
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Activer / désactiver rapidement PATCH /users/{id}/toggle
     */
    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);
            $oldStatus = $user->is_active;
            
            $user->is_active = !$user->is_active;
            $user->save();

            $newStatusText = $user->is_active ? 'activé' : 'désactivé';

            DB::commit();

            // ✅ Log changement de statut
            ActivityLogger::success(
                ActivityAction::USER_STATUS_TOGGLED,
                "a {$newStatusText} l'utilisateur {$user->name} ({$user->username})",
                [
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                    'metadata' => [
                        'old_status' => $oldStatus,
                        'new_status' => $user->is_active,
                        'action' => $newStatusText,
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
                'message' => 'Statut modifié',
                'is_active' => $user->is_active,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}