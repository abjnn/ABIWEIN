<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Crée l'archive immuable des rapports administratifs de l'exploitation. */
    public function up(): void
    {
        Schema::create('farm_reports', function (Blueprint $table): void {
            $table->id()->comment('Identifiant interne du rapport');
            $table->string('reference', 50)->unique()->comment('Référence administrative unique');
            $table->string('type', 40)->index()->comment('Nature du rapport généré');
            $table->string('title', 180)->comment('Objet administratif du document');
            $table->date('report_date')->index()->comment('Date officielle portée sur le rapport');
            $table->date('period_start')->index()->comment('Premier jour couvert par les données');
            $table->date('period_end')->index()->comment('Dernier jour couvert par les données');
            $table->json('snapshot')->comment('Copie figée des indicateurs au moment de la génération');
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete()->comment('Responsable ayant produit le rapport');
            $table->timestamps();
            $table->index(['type', 'report_date']);
        });
    }

    /** Supprime uniquement l'archive de rapports lors d'un retour de migration. */
    public function down(): void
    {
        Schema::dropIfExists('farm_reports');
    }
};
