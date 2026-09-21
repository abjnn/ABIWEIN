<?php

use App\Http\Controllers\Stock\ProductController;
use App\Http\Controllers\Stock\StockMovementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestion des stocks ABIWEIN
|--------------------------------------------------------------------------
| L’ordre place les URL fixes « mouvements » avant le paramètre produit.
*/
// L'administrateur supervise le registre et le responsable le tient à jour.
Route::middleware(['auth', 'active', 'role:administrator,farm_manager'])
    ->prefix('stocks')
    ->name('stocks.')
    ->group(function (): void {
        // Ces routes GET sont strictement consultatives et ouvertes aux deux rôles.
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/mouvements', [StockMovementController::class, 'index'])->name('movements.index');
        Route::get('/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('show');
    });

// Seul le responsable d'exploitation peut modifier les intrants de la ferme.
Route::middleware(['auth', 'active', 'role:farm_manager'])
    ->prefix('stocks')
    ->name('stocks.')
    ->group(function (): void {
        Route::get('/creer', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/mouvements/creer', [StockMovementController::class, 'create'])->name('movements.create');
        Route::post('/mouvements', [StockMovementController::class, 'store'])->name('movements.store');
        Route::get('/{product}/modifier', [ProductController::class, 'edit'])->whereNumber('product')->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('update');
        Route::patch('/{product}/statut', [ProductController::class, 'toggleStatus'])->whereNumber('product')->name('toggle-status');
    });
