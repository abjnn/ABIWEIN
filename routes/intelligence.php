<?php

use App\Http\Controllers\Intelligence\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Assistant de pilotage ABIWEIN
|--------------------------------------------------------------------------
| Le vétérinaire est volontairement exclu : seuls l'administrateur et le
| responsable d'exploitation utilisent cet espace d'aide à la décision.
*/
Route::middleware(['auth', 'active', 'role:administrator,farm_manager'])
    ->prefix('intelligence-artificielle')
    ->name('intelligence.')
    ->group(function (): void {
        // Le nom « demo » reste stable pour les liens existants de la barre latérale.
        Route::get('/', [ChatController::class, 'index'])->name('demo');
        // Cette route reçoit une question avec une limitation renforcée par compte/IP.
        Route::post('/messages', [ChatController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('messages.store');
        // L'utilisateur peut repartir d'une conversation vide sans toucher aux autres comptes.
        Route::delete('/conversation', [ChatController::class, 'destroy'])
            ->name('conversation.destroy');
    });
