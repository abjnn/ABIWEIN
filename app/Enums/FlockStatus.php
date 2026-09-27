<?php

namespace App\Enums;

/** Cycle de vie volontairement court d'une bande ABIWEIN. */
enum FlockStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Archived = 'archived';

    /** Libellé français commun aux listes et aux fiches. */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Closed => 'Clôturée',
            self::Archived => 'Archivée',
        };
    }
}
