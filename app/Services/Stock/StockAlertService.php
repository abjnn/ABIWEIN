<?php

namespace App\Services\Stock;

use App\Models\Alert;
use App\Models\Product;

/** Maintient les alertes de seuil et d’expiration liées à un produit. */
class StockAlertService
{
    /** Synchronise toutes les alertes de stock après une création ou une modification. */
    public function sync(Product $product): void
    {
        $this->syncThresholdAlert($product);
        $this->syncExpirationAlert($product);
    }

    /** Ouvre l’alerte lorsque le stock atteint le seuil et la résout après réapprovisionnement. */
    private function syncThresholdAlert(Product $product): void
    {
        $isBelowThreshold = $product->is_active
            && (float) $product->current_stock <= (float) $product->alert_threshold;

        if (! $isBelowThreshold) {
            $this->resolve($product, 'stock_threshold');

            return;
        }

        $this->openOrRefresh($product, 'stock_threshold', [
            'severity' => (float) $product->current_stock <= 0 ? 'critical' : 'warning',
            'title' => "Stock faible : {$product->name}",
            'message' => "Le stock disponible ({$product->current_stock} {$product->unit}) a atteint le seuil défini ({$product->alert_threshold} {$product->unit}).",
        ]);
    }

    /** Ouvre une alerte trente jours avant l’échéance et renforce sa gravité après expiration. */
    private function syncExpirationAlert(Product $product): void
    {
        $expiresAt = $product->expires_at?->startOfDay();
        $today = today();

        if (! $product->is_active || ! $expiresAt || $expiresAt->isAfter($today->copy()->addDays(30))) {
            $this->resolve($product, 'stock_expiration');

            return;
        }

        $isExpired = $expiresAt->isBefore($today);
        $this->openOrRefresh($product, 'stock_expiration', [
            'severity' => $isExpired ? 'critical' : 'warning',
            'title' => $isExpired ? "Produit expiré : {$product->name}" : "Expiration proche : {$product->name}",
            'message' => $isExpired
                ? "Ce produit a expiré le {$expiresAt->format('d/m/Y')} et ne doit plus être utilisé."
                : "Ce produit expire le {$expiresAt->format('d/m/Y')}. Planifiez son utilisation ou son remplacement.",
        ]);
    }

    /** Crée l’alerte ou actualise son contenu sans effacer un acquittement humain existant. */
    private function openOrRefresh(Product $product, string $type, array $content): void
    {
        $alert = Alert::query()->firstOrNew([
            'type' => $type,
            'source_type' => Product::class,
            'source_id' => $product->id,
        ]);

        $alert->fill($content);

        // Une alerte déjà acquittée reste acquittée ; une alerte résolue est rouverte si le risque revient.
        if (! $alert->exists || $alert->status === 'resolved') {
            $alert->status = 'open';
            $alert->resolved_at = null;
            $alert->acknowledged_by = null;
            $alert->acknowledged_at = null;
        }

        $alert->save();
    }

    /** Marque comme résolue une alerte dont la condition n’est plus vraie. */
    private function resolve(Product $product, string $type): void
    {
        Alert::query()
            ->where('type', $type)
            ->where('source_type', Product::class)
            ->where('source_id', $product->id)
            ->where('status', '!=', 'resolved')
            ->update(['status' => 'resolved', 'resolved_at' => now()]);
    }
}
