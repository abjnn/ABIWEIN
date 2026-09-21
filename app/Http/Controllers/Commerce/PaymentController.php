<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StorePaymentRequest;
use App\Models\Sale;
use App\Services\Commerce\SaleService;
use Illuminate\Http\RedirectResponse;

/** Enregistre les versements en espèces effectués sur les ventes à crédit. */
class PaymentController extends Controller
{
    /** Ajoute le versement puis revient sur le dossier de la vente concernée. */
    public function store(StorePaymentRequest $request, Sale $sale, SaleService $saleService): RedirectResponse
    {
        $saleService->registerPayment($sale, $request->validated(), $request->user());

        return to_route('sales.show', $sale)->with('status', 'Le versement en espèces a été enregistré.');
    }
}
