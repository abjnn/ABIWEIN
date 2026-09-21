<?php

use App\Http\Controllers\Health\VeterinaryVisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes du module sanitaire ABIWEIN
|--------------------------------------------------------------------------
| Le fichier doit être chargé depuis routes/web.php pour activer le module.
| Les commentaires sous chaque route indiquent précisément son écran.
*/

// Les trois rôles peuvent parcourir l’historique sanitaire.
Route::middleware(['auth', 'active', 'role:administrator,farm_manager,veterinarian'])
    ->prefix('sante')
    ->name('health.')
    ->group(function (): void {
        // Cette URL affiche la liste filtrable des visites vétérinaires.
        Route::get('/visites', [VeterinaryVisitController::class, 'index'])->name('visits.index');
    });

// Seuls l’administrateur et le vétérinaire peuvent créer un diagnostic sanitaire.
Route::middleware(['auth', 'active', 'role:administrator,veterinarian'])
    ->prefix('sante')
    ->name('health.')
    ->group(function (): void {
        // Cette URL ouvre le formulaire ; elle précède volontairement la route avec identifiant.
        Route::get('/visites/nouvelle', [VeterinaryVisitController::class, 'create'])->name('visits.create');
        // Cette URL valide puis enregistre définitivement la visite.
        Route::post('/visites', [VeterinaryVisitController::class, 'store'])->name('visits.store');
    });

// La route détaillée est placée après « nouvelle » pour éviter toute ambiguïté d’URL.
Route::middleware(['auth', 'active', 'role:administrator,farm_manager,veterinarian'])
    ->prefix('sante')
    ->name('health.')
    ->group(function (): void {
        // Cette URL affiche une fiche existante ; whereNumber refuse les identifiants non numériques.
        Route::get('/visites/{veterinaryVisit}', [VeterinaryVisitController::class, 'show'])
            ->whereNumber('veterinaryVisit')
            ->name('visits.show');
    });
