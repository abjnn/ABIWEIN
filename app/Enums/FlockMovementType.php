<?php

namespace App\Enums;

/** Natures techniques inscrites dans le journal immuable des effectifs. */
enum FlockMovementType: string
{
    case Arrival = 'arrival';
    case Mortality = 'mortality';
    case MortalityCorrection = 'mortality_correction';
    case Transfer = 'transfer';
    case Closure = 'closure';
}
