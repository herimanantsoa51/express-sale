<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
     * Liste des utilisateurs
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
     * Créer un utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:4',
            'role' => 'required|in:admin,user,vendeur',
            'is_active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé',
            'user' => $user,
        ], 201);
    }

    /**
     * Modifier nom / rôle / activation
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'role' => 'sometimes|in:admin,user,vendeur',
            'is_active' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:4',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Utilisateur mis à jour',
            'user' => $user,
        ]);
    }

    /**
     * Activer / désactiver rapidement
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = ! $user->is_active;
        $user->save();

        return response()->json([
            'message' => 'Statut modifié',
            'is_active' => $user->is_active,
        ]);
    }
}
