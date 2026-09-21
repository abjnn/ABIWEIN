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
Route::middleware(['auth', 'active', 'role:administrator,farm_manager'])
    ->prefix('stocks')
    ->name('stocks.')
    ->group(function (): void {
        // Ces trois routes affichent, préparent puis enregistrent un produit.
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/creer', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');

        // Ces routes gèrent le journal des entrées, sorties et ajustements.
        Route::get('/mouvements', [StockMovementController::class, 'index'])->name('movements.index');
        Route::get('/mouvements/creer', [StockMovementController::class, 'create'])->name('movements.create');
        Route::post('/mouvements', [StockMovementController::class, 'store'])->name('movements.store');

        // Les URL suivantes travaillent sur un produit existant identifié par Laravel.
        Route::get('/{product}', [ProductController::class, 'show'])->name('show');
        Route::get('/{product}/modifier', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::patch('/{product}/statut', [ProductController::class, 'toggleStatus'])->name('toggle-status');
    });
