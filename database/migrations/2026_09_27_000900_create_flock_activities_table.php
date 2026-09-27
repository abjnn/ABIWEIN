<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Crée la chronologie détaillée qui complète le résumé quotidien existant. */
    public function up(): void
    {
        Schema::create('flock_activities', function (Blueprint $table): void {
            $table->id()->comment('Identifiant de l’action quotidienne');
            $table->foreignId('flock_id')->constrained()->cascadeOnDelete()->comment('Bande concernée');
            $table->foreignId('daily_record_id')->nullable()->constrained()->nullOnDelete()->comment('Résumé du même jour');
            $table->timestamp('occurred_at')->index()->comment('Date et heure réelles de l’action');
            $table->string('type', 30)->index()->comment('Inspection, aliment, eau, pesée ou autre action');
            $table->text('description')->comment('Compte rendu lisible de l’action');
            $table->decimal('quantity', 10, 2)->nullable()->comment('Mesure éventuelle associée à l’action');
            $table->string('unit', 20)->nullable()->comment('Unité imposée par le type d’action');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete()->comment('Responsable ayant saisi l’action');
            $table->timestamps();
            $table->index(['flock_id', 'occurred_at']);
        });
    }

    /** Retire uniquement la table ajoutée par ce module. */
    public function down(): void
    {
        Schema::dropIfExists('flock_activities');
    }
};
