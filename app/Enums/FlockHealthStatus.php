<?php

namespace App\Enums;

/** États techniques autorisés pour un relevé quotidien. */
enum FlockHealthStatus: string
{
    case Good = 'good';
    case Watch = 'watch';
    case Critical = 'critical';

    /** Libellé court affiché dans les écrans de suivi. */
    public function label(): string
    {
        return match ($this) {
            self::Good => 'Bon',
            self::Watch => 'À surveiller',
            self::Critical => 'Critique',
        };
    }
}
