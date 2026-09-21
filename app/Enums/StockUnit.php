<?php

namespace App\Enums;

/**
 * Liste les unités employées par ABIWEIN afin d’éviter des variantes incohérentes.
 * Le texte stocké en base reste court et stable, tandis que label() gère l’affichage.
 */
enum StockUnit: string
{
    case Kilogram = 'kg';
    case Liter = 'litre';
    case Bag = 'sac';
    case Bottle = 'flacon';
    case Piece = 'pièce';
    case Box = 'boîte';
    case Carton = 'carton';

    /** Fournit un libellé explicite dans le sélecteur d’unité. */
    public function label(): string
    {
        return match ($this) {
            self::Kilogram => 'Kilogramme (kg)',
            self::Liter => 'Litre',
            self::Bag => 'Sac',
            self::Bottle => 'Flacon',
            self::Piece => 'Pièce',
            self::Box => 'Boîte',
            self::Carton => 'Carton',
        };
    }
}
