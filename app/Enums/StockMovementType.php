<?php

namespace App\Enums;

/**
 * Définit les trois opérations autorisées sur un stock.
 * Un ajustement remplace la quantité théorique par la quantité réellement comptée.
 */
enum StockMovementType: string
{
    case Entry = 'entry';
    case Exit = 'exit';
    case Adjustment = 'adjustment';

    /** Retourne le libellé destiné à l’interface française. */
    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entrée',
            self::Exit => 'Sortie',
            self::Adjustment => 'Ajustement d’inventaire',
        };
    }
}
