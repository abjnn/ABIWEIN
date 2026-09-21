<?php

use App\Http\Controllers\Intelligence\DemonstrationAnalysisController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Intelligence artificielle — démonstration locale
|--------------------------------------------------------------------------
| Aucun rôle métier n'est exclu ici. Le service filtre lui-même les données
| sensibles avant de construire la réponse destinée à l'utilisateur.
*/
Route::middleware(['auth', 'active'])
    ->prefix('intelligence-artificielle')
    ->name('intelligence.')
    ->group(function (): void {
        // Cette page est uniquement consultative et ne déclenche aucune action automatisée.
        Route::get('/', DemonstrationAnalysisController::class)->name('demo');
    });
