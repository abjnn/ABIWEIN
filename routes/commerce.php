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
// Consultation : l'administrateur supervise et le responsable exploite ces données.
Route::middleware(['auth', 'active', 'role:administrator,farm_manager'])
    ->prefix('gestion')
    ->group(function (): void {
        Route::get('/commerce', CommerceDashboardController::class)->name('commerce.dashboard');
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/ventes', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/ventes/{sale}', [SaleController::class, 'show'])->whereNumber('sale')->name('sales.show');
        Route::get('/creances', ReceivableController::class)->name('receivables.index');
        Route::get('/depenses', [ExpenseController::class, 'index'])->name('expenses.index');
    });

// Écriture : toutes les opérations commerciales appartiennent au responsable d'exploitation.
Route::middleware(['auth', 'active', 'role:farm_manager'])
    ->prefix('gestion')
    ->group(function (): void {
        Route::get('/clients/creer', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}/modifier', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::patch('/clients/{client}/statut', [ClientController::class, 'toggleStatus'])->name('clients.toggle-status');
        Route::get('/ventes/creer', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/ventes', [SaleController::class, 'store'])->name('sales.store');
        Route::post('/ventes/{sale}/paiements', [PaymentController::class, 'store'])->name('sales.payments.store');
        Route::get('/depenses/creer', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/depenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/depenses/{expense}/modifier', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/depenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    });
