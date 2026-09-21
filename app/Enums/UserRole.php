<?php

namespace App\Enums;

/** Centralise les rôles afin d’éviter les chaînes différentes dans le code. */
enum UserRole: string
{
    // Accès complet : comptes, paramètres et supervision générale.
    case Administrator = 'administrator';

    // Pilotage quotidien de l’exploitation, des stocks et des ventes.
    case FarmManager = 'farm_manager';

    // Accès ciblé aux informations et opérations sanitaires.
    case Veterinarian = 'veterinarian';

    /** Retourne le libellé français affiché dans l’interface. */
    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrateur',
            self::FarmManager => 'Responsable d’exploitation',
            self::Veterinarian => 'Vétérinaire',
        };
    }

    /** Fournit toutes les valeurs utilisables par les validateurs Laravel. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
