<?php

namespace App\Support;

class AiTaskSchema
{
    public const TYPES = [
        'ui_action' => [
            'redirect',
            'open_url',
            'toast',
            'set_field',
            'focus_field',
        ],
        'data_action' => [
            'autosave',
            'upsert',
            'validate',
            'fetch',
        ],
        'insight' => [
            'analyze_db',
            'summary',
            'anomaly',
            'kpi',
        ],
        'advisor' => [
            'message',
            'recommendation',
            'next_steps',
        ],
    ];

    public static function schemaText(): string
    {
        $lines = ["Schéma des tâches autorisées:"];
        foreach (self::TYPES as $type => $actions) {
            $lines[] = "- {$type}: " . implode(', ', $actions);
        }
        $lines[] = "Format strict: {\"tasks\":[{\"type\":\"...\",\"action\":\"...\",\"payload\":{}}]}";
        return implode("\n", $lines);
    }

    public static function isAllowed(string $type, string $action): bool
    {
        return isset(self::TYPES[$type]) && in_array($action, self::TYPES[$type], true);
    }
}
