<?php

namespace App\Enums;

/**
 * Centralise les familles de produits autorisées dans le module Stocks.
 * Pour ajouter une famille, créez un nouveau cas puis ajoutez son libellé dans label().
 */
enum ProductCategory: string
{
    case Feed = 'feed';
    case Vaccine = 'vaccine';
    case Antibiotic = 'antibiotic';
    case Vitamin = 'vitamin';
    case Health = 'health';
    case Equipment = 'equipment';
    case Packaging = 'packaging';
    case Other = 'other';

    /** Retourne le texte français présenté dans les formulaires et tableaux. */
    public function label(): string
    {
        return match ($this) {
            self::Feed => 'Aliments',
            self::Vaccine => 'Vaccins',
            self::Antibiotic => 'Antibiotiques vétérinaires',
            self::Vitamin => 'Vitamines et électrolytes',
            self::Health => 'Produits sanitaires',
            self::Equipment => 'Équipements',
            self::Packaging => 'Emballages',
            self::Other => 'Autres produits',
        };
    }
}
