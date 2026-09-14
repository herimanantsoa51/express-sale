<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Gère les images de la boutique : action=upload (image en base64 → stockée sur le disque public, retourne le chemin à utiliser dans image_url / image_path des produits et variantes) ou action=delete (supprime une image par son chemin). Formats : jpeg, png, jpg, gif, webp. 5 Mo maximum.')]
class ManageImageTool extends Tool
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    private const MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'action' => 'required|string|in:upload,delete',
            'image_base64' => 'nullable|string',
            'filename' => 'nullable|string|max:100',
            'path' => 'nullable|string|max:500',
        ], [
            'action.required' => "L'action est requise (action) : upload ou delete.",
        ]);

        if ($input['action'] === 'delete') {
            if (empty($input['path'])) {
                return Response::error("Le chemin de l'image est requis pour la suppression (path).");
            }

            $relativePath = ltrim($input['path'], '/');
            if (! Storage::disk('public')->exists($relativePath)) {
                return Response::error("Aucune image ne correspond au chemin fourni.");
            }

            Storage::disk('public')->delete($relativePath);

            ActivityLogger::success(
                ActivityAction::FILE_UPLOADED,
                "Image supprimée via MCP : {$relativePath}",
                ['model_type' => null, 'model_id' => null, 'metadata' => ['path' => $relativePath]]
            );

            return Response::json([
                'message' => 'Image supprimée avec succès.',
                'path' => $relativePath,
            ]);
        }

        if (empty($input['image_base64'])) {
            return Response::error("Le contenu de l'image en base64 est requis pour l'upload (image_base64).");
        }

        $binary = base64_decode($input['image_base64'], true);
        if ($binary === false) {
            return Response::error("Le contenu base64 est invalide.");
        }
        if (strlen($binary) > self::MAX_BYTES) {
            return Response::error("L'image dépasse la taille maximale de 5 Mo.");
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        if (! isset(self::MIME_TO_EXT[$mime])) {
            return Response::error("Format d'image non supporté ({$mime}) — formats acceptés : jpeg, png, gif, webp.");
        }

        $extension = self::MIME_TO_EXT[$mime];
        $fileName = Str::uuid().'.'.$extension;
        $relativePath = 'uploads/images/'.date('Y/m').'/'.$fileName;

        Storage::disk('public')->put($relativePath, $binary);

        ActivityLogger::success(
            ActivityAction::FILE_UPLOADED,
            "Image uploadée via MCP : {$relativePath}",
            [
                'model_type' => null,
                'model_id' => null,
                'metadata' => ['path' => $relativePath, 'mime' => $mime, 'size' => strlen($binary), 'original_name' => $input['filename'] ?? null],
            ]
        );

        return Response::json([
            'message' => 'Image uploadée avec succès.',
            'path' => $relativePath,
            'usage' => "Utiliser ce chemin dans image_url (produit) ou image_path (variante) pour l'afficher dans la boutique.",
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description("'upload' ou 'delete'.")
                ->required()
                ->enum(['upload', 'delete']),
            'image_base64' => $schema->string()
                ->description("Contenu de l'image encodé en base64 (requis pour upload, 5 Mo max, formats jpeg/png/gif/webp)."),
            'filename' => $schema->string()
                ->description("Nom original du fichier (optionnel, pour la traçabilité).")
                ->max(100),
            'path' => $schema->string()
                ->description("Chemin de l'image à supprimer (requis pour delete).")
                ->max(500),
        ];
    }
}
