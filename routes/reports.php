<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rapports administratifs ABIWEIN
|--------------------------------------------------------------------------
| L'administrateur supervise en lecture seule. Le responsable d'exploitation
| est le seul rôle autorisé à générer un nouveau document archivé.
*/
Route::middleware(['auth', 'active', 'role:administrator,farm_manager'])
    ->prefix('rapports')
    ->name('reports.')
    ->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');

        // Les routes fixes précèdent le modèle implicite afin que "creer" ne soit jamais traité comme un identifiant.
        Route::middleware('role:farm_manager')->group(function (): void {
            Route::get('/creer', [ReportController::class, 'create'])->name('create');
            Route::post('/', [ReportController::class, 'store'])->name('store');
        });

        Route::get('/{farmReport}', [ReportController::class, 'show'])->name('show');
        Route::get('/{farmReport}/pdf', [ReportController::class, 'download'])->name('pdf');
    });
