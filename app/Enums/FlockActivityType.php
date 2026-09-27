<?php

namespace App\Enums;

/** Types d'actions pouvant composer le journal chronologique d'une bande. */
enum FlockActivityType: string
{
    case Inspection = 'inspection';
    case Feeding = 'feeding';
    case Watering = 'watering';
    case Weighing = 'weighing';
    case Mortality = 'mortality';
    case Environment = 'environment';
    case Cleaning = 'cleaning';
    case AppliedCare = 'applied_care';
    case Other = 'other';

    /** Libellé français utilisé dans les formulaires et la chronologie. */
    public function label(): string
    {
        return match ($this) {
            self::Inspection => 'Inspection de la bande',
            self::Feeding => 'Distribution d’aliment',
            self::Watering => 'Distribution d’eau',
            self::Weighing => 'Pesée de contrôle',
            self::Mortality => 'Mortalité constatée',
            self::Environment => 'Contrôle de l’ambiance',
            self::Cleaning => 'Nettoyage ou désinfection',
            self::AppliedCare => 'Soin recommandé appliqué',
            self::Other => 'Autre intervention',
        };
    }

    /** Unité imposée côté serveur afin d'éviter des données contradictoires. */
    public function unit(): ?string
    {
        return match ($this) {
            self::Feeding, self::Weighing => 'kg',
            self::Watering => 'L',
            self::Mortality => 'sujet(s)',
            default => null,
        };
    }

    /** Indique si l'action nécessite une mesure numérique. */
    public function requiresQuantity(): bool
    {
        return in_array($this, [self::Feeding, self::Watering, self::Weighing, self::Mortality], true);
    }
}
