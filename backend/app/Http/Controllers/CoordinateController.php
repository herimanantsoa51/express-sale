<?php

namespace App\Http\Controllers;

use App\Models\Coordinate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoordinateController extends Controller
{
    /**
     * GET /api/coordinates
     * Liste des coordonnées avec filtres optionnels
     */
    public function index(Request $request)
    {
        $query = Coordinate::query();

        // Filtre par pays
        if ($request->has('country')) {
            $query->where('country', $request->country);
        }

        // Filtre par ville
        if ($request->has('city')) {
            $query->where('city', 'LIKE', "%{$request->city}%");
        }

        // Filtre par statut
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Recherche globale
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('country', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%");
            });
        }

        // Trier par pays puis ville
        $coordinates = $query->orderBy('country')
                            ->orderBy('city')
                            ->get();

        return response()->json($coordinates);
    }

    /**
     * GET /api/coordinates/countries
     * Liste unique des pays
     */
    public function countries()
    {
        $countries = Coordinate::select('country')
            ->where('is_active', true)
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return response()->json($countries);
    }

    /**
     * GET /api/coordinates/cities/{country}
     * Liste des villes pour un pays donné
     */
    public function citiesByCountry($country)
    {
        $cities = Coordinate::where('country', $country)
            ->where('is_active', true)
            ->orderBy('city')
            ->pluck('city', 'id');

        return response()->json($cities);
    }

    /**
     * POST /api/coordinates
     * Créer une nouvelle coordonnée
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'is_active' => 'boolean'
        ]);

        // Vérifier l'unicité country + city
        $exists = Coordinate::where('country', $data['country'])
            ->where('city', $data['city'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cette combinaison pays/ville existe déjà'
            ], 422);
        }

        $coordinate = Coordinate::create($data);

        return response()->json($coordinate, 201);
    }

    /**
     * GET /api/coordinates/{id}
     */
    public function show($id)
    {
        $coordinate = Coordinate::with(['suppliers', 'freightForwarders'])
            ->findOrFail($id);

        return response()->json($coordinate);
    }

    /**
     * PUT /api/coordinates/{id}
     */
    public function update(Request $request, $id)
    {
        $coordinate = Coordinate::findOrFail($id);

        $data = $request->validate([
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'is_active' => 'boolean'
        ]);

        // Vérifier l'unicité sauf pour cette coordonnée
        $exists = Coordinate::where('country', $data['country'])
            ->where('city', $data['city'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cette combinaison pays/ville existe déjà'
            ], 422);
        }

        $coordinate->update($data);

        return response()->json($coordinate);
    }

    /**
     * DELETE /api/coordinates/{id}
     */
    public function destroy($id)
    {
        $coordinate = Coordinate::findOrFail($id);

        // Vérifier si utilisé
        $suppliersCount = $coordinate->suppliers()->count();
        $forwardersCount = $coordinate->freightForwarders()->count();

        if ($suppliersCount > 0 || $forwardersCount > 0) {
            return response()->json([
                'message' => "Impossible de supprimer : utilisé par {$suppliersCount} fournisseur(s) et {$forwardersCount} transitaire(s)"
            ], 409);
        }

        $coordinate->delete();

        return response()->json([
            'message' => 'Coordonnée supprimée'
        ]);
    }

    /**
     * GET /api/coordinates/{id}/usage
     * Statistiques d'utilisation
     */
    public function usage($id)
    {
        $coordinate = Coordinate::findOrFail($id);

        return response()->json([
            'suppliers_count' => $coordinate->suppliers()->count(),
            'forwarders_count' => $coordinate->freightForwarders()->count(),
            'suppliers' => $coordinate->suppliers()->select('id', 'name')->get(),
            'forwarders' => $coordinate->freightForwarders()->select('id', 'name')->get(),
        ]);
    }
}