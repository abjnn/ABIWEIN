<?php

use App\Http\Controllers\Commerce\ClientController;
use App\Http\Controllers\Commerce\CommerceDashboardController;
use App\Http\Controllers\Commerce\ExpenseController;
use App\Http\Controllers\Commerce\PaymentController;
use App\Http\Controllers\Commerce\ReceivableController;
use App\Http\Controllers\Commerce\SaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestion commerciale et financière ABIWEIN
|--------------------------------------------------------------------------
| Le préfixe « gestion » regroupe les URL sans modifier les noms de routes
| utilisés dans les vues. Seuls les rôles de pilotage y ont accès.
*/
Route::middleware(['auth', 'active', 'role:administrator,farm_manager'])
    ->prefix('gestion')
    ->group(function (): void {
        // Cette page rassemble les principaux indicateurs commerciaux.
        Route::get('/commerce', CommerceDashboardController::class)->name('commerce.dashboard');

        // Ces routes gèrent le répertoire des clients de la ferme.
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/creer', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}/modifier', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::patch('/clients/{client}/statut', [ClientController::class, 'toggleStatus'])->name('clients.toggle-status');

        // Ces routes enregistrent les ventes, leurs lignes et leurs paiements en espèces.
        Route::get('/ventes', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/ventes/creer', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/ventes', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/ventes/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::post('/ventes/{sale}/paiements', [PaymentController::class, 'store'])->name('sales.payments.store');

        // Cette route affiche uniquement les ventes qui gardent un solde à recouvrer.
        Route::get('/creances', ReceivableController::class)->name('receivables.index');

        // Ces routes constituent le journal des sorties de caisse.
        Route::get('/depenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/depenses/creer', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/depenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/depenses/{expense}/modifier', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/depenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    });
