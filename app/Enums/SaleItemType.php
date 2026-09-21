<?php

namespace App\Enums;

/** Types de poulets commercialisés, volontairement séparés du stock d'intrants. */
enum SaleItemType: string
{
    case LiveChicken = 'live_chicken';
    case PreparedChicken = 'prepared_chicken';
    case BroilerBatch = 'broiler_batch';

    /** Libellé figé dans la ligne de vente au moment de l'enregistrement. */
    public function label(): string
    {
        return match ($this) {
            self::LiveChicken => 'Poulet de chair vivant',
            self::PreparedChicken => 'Poulet de chair préparé',
            self::BroilerBatch => 'Lot de poulets de chair',
        };
    }
}
