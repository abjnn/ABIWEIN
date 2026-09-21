<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rapports ABIWEIN
|--------------------------------------------------------------------------
| Les données commerciales et financières restent réservées aux deux rôles
| qui pilotent l’exploitation. Le vétérinaire conserve son espace sanitaire.
*/
Route::middleware(['auth', 'active', 'role:administrator,farm_manager'])
    ->get('/rapports', [ReportController::class, 'index'])
    ->name('reports.index');
