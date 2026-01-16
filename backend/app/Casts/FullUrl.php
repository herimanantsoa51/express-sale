<?php
// app/Casts/FullUrl.php
namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class FullUrl implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (!$value) return null;

        // Si ce n'est pas déjà une URL complète
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return rtrim(config('app.url'), '/') . '/storage/' . ltrim($value, '/');
        }

        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        // On garde la valeur telle quelle pour l'écriture dans la DB
        return $value;
    }
}
