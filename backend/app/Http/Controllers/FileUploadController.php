<?php
// app/Http/Controllers/FileUploadController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
class FileUploadController extends Controller
{
    /**
     * Upload une image
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        try {
            // Créer un nom de fichier unique
            $fileName = Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            
            // Chemin de stockage
            $path = 'uploads/images/' . date('Y/m');
            
            // Uploader le fichier dans le disque "public"
            $filePath = $request->file('image')->storeAs($path, $fileName, 'public');
            ActivityLogger::success(
                ActivityAction::FILE_UPLOADED,
                "Image uploadée : {$filePath}",
                [
                    'model_type' => null,
                    'model_id' => null,
                    'metadata' => ['path' => $filePath],
                ]
            );
            // On **stocke seulement le chemin relatif**, pas l’URL complète
            // URL finale sera construite dynamiquement avec asset($image_path) côté front
            return response()->json([
                'success' => true,
                'url' => $filePath,
                'message' => 'Image uploadée avec succès'
            ]);
            
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::FILE_UPLOADED,
                "Erreur lors de l'upload de l'image",
                $e,
               
            );
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une image
     */
    public function deleteImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            // Supprimer le fichier du stockage
            if (Storage::disk('public')->exists($request->path)) {
                Storage::disk('public')->delete($request->path);
            }
            ActivityLogger::success(
                ActivityAction::FILE_UPLOADED,
                "Image supprimée : {$request->path}",
                [
                    'model_type' => null,
                    'model_id' => null,
                    'metadata' => ['path' => $request->path],
                ]
            );
            return response()->json([
                'success' => true,
                'message' => 'Image supprimée avec succès'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
