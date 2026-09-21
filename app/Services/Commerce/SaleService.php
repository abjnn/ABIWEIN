<?php

namespace App\Services\Commerce;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Centralise les calculs financiers et les mouvements de stock d'une vente.
 * Les contrôleurs restent ainsi courts et aucune interface ne peut contourner ces règles.
 */
class SaleService
{
    /**
     * Crée la vente, ses lignes, le paiement initial et les sorties de stock
     * dans une seule transaction : une erreur annule l'ensemble de l'opération.
     */
    public function create(array $data, User $actor): Sale
    {
        return DB::transaction(function () use ($data, $actor): Sale {
            // Le verrou empêche deux ventes simultanées de consommer le même stock.
            $products = Product::query()
                ->whereIn('id', collect($data['items'])->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $preparedItems = [];
            $totalAmount = 0.0;

            // Chaque ligne est recalculée côté serveur et comparée au stock disponible.
            foreach ($data['items'] as $index => $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = round((float) $item['quantity'], 2);
                $unitPrice = round((float) $item['unit_price'], 2);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        "items.$index.product_id" => 'Ce produit n’est plus disponible à la vente.',
                    ]);
                }

                if ((float) $product->current_stock < $quantity) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => "Stock insuffisant pour {$product->name} : {$product->current_stock} {$product->unit} disponible(s).",
                    ]);
                }

                // MODIFICATION : changez ici l'arrondi si la comptabilité adopte un autre nombre de décimales.
                $lineTotal = round($quantity * $unitPrice, 2);
                $totalAmount = round($totalAmount + $lineTotal, 2);
                $preparedItems[] = compact('product', 'quantity', 'unitPrice', 'lineTotal');
            }

            // Une vente au comptant est toujours intégralement payée dès sa création.
            $paidAmount = $data['sale_type'] === 'cash'
                ? $totalAmount
                : round((float) ($data['initial_payment'] ?? 0), 2);
            $balanceDue = round($totalAmount - $paidAmount, 2);

            // Cette sécurité reste active même si le service est appelé sans le formulaire habituel.
            if ($paidAmount > $totalAmount) {
                throw ValidationException::withMessages([
                    'initial_payment' => 'Le premier versement ne peut pas dépasser le montant total de la vente.',
                ]);
            }

            // Le statut est déduit des montants et n'est jamais fourni par le navigateur.
            $status = $balanceDue <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');
            $reference = $this->uniqueReference('VTE', Sale::class);

            $sale = Sale::query()->create([
                'reference' => $reference,
                'client_id' => $data['client_id'] ?? null,
                'sale_date' => $data['sale_date'],
                'sale_type' => $data['sale_type'],
                'payment_method' => 'cash',
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'balance_due' => $balanceDue,
                'status' => $status,
                'created_by' => $actor->id,
            ]);

            foreach ($preparedItems as $preparedItem) {
                /** @var Product $product */
                $product = $preparedItem['product'];

                // La ligne conserve le nom et le prix appliqués au jour de la vente.
                $sale->items()->create([
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $preparedItem['quantity'],
                    'unit_price' => $preparedItem['unitPrice'],
                    'line_total' => $preparedItem['lineTotal'],
                ]);

                // La quantité disponible et son historique sont mis à jour ensemble.
                $product->decrement('current_stock', $preparedItem['quantity']);
                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'type' => 'exit',
                    'quantity' => $preparedItem['quantity'],
                    'occurred_at' => now(),
                    'reason' => "Vente {$reference}",
                    'recorded_by' => $actor->id,
                ]);
            }

            // Tout montant encaissé produit un reçu en espèces consultable dans l'historique.
            if ($paidAmount > 0) {
                $sale->payments()->create([
                    'amount' => $paidAmount,
                    'paid_at' => now(),
                    'payment_method' => 'cash',
                    'reference' => $this->uniqueReference('REG', Payment::class),
                    'recorded_by' => $actor->id,
                ]);
            }

            return $sale->load(['client', 'items.product', 'payments.recordedBy', 'creator']);
        });
    }

    /** Enregistre un versement en espèces puis recalcule le solde de la vente verrouillée. */
    public function registerPayment(Sale $sale, array $data, User $actor): Payment
    {
        return DB::transaction(function () use ($sale, $data, $actor): Payment {
            // Le rechargement verrouillé empêche deux encaissements de dépasser simultanément le solde.
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $amount = round((float) $data['amount'], 2);

            if ((float) $lockedSale->balance_due <= 0) {
                throw ValidationException::withMessages(['amount' => 'Cette vente est déjà entièrement réglée.']);
            }

            if ($amount > (float) $lockedSale->balance_due) {
                throw ValidationException::withMessages([
                    'amount' => 'Le versement ne peut pas dépasser le solde restant de '.number_format((float) $lockedSale->balance_due, 0, ',', ' ').' FCFA.',
                ]);
            }

            $payment = $lockedSale->payments()->create([
                'amount' => $amount,
                'paid_at' => $data['paid_at'],
                'payment_method' => 'cash',
                'reference' => ($data['reference'] ?? null) ?: $this->uniqueReference('REG', Payment::class),
                'recorded_by' => $actor->id,
            ]);

            // La somme des reçus enregistrés devient la source de vérité du montant payé.
            $paidAmount = round((float) $lockedSale->payments()->sum('amount'), 2);
            $balanceDue = max(0, round((float) $lockedSale->total_amount - $paidAmount, 2));
            $lockedSale->update([
                'paid_amount' => $paidAmount,
                'balance_due' => $balanceDue,
                'status' => $balanceDue <= 0 ? 'paid' : 'partial',
            ]);

            return $payment;
        });
    }

    /** Produit une référence courte et vérifie son unicité avant l'écriture. */
    private function uniqueReference(string $prefix, string $modelClass): string
    {
        do {
            // MODIFICATION : la structure visuelle des références se change sur cette ligne uniquement.
            $reference = $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while ($modelClass::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
