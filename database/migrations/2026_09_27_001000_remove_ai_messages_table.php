<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supprime les conversations de l'ancien module d'intelligence artificielle.
     * Cette migration ciblée conserve toutes les autres données d'ABIWEIN.
     */
    public function up(): void
    {
        Schema::dropIfExists('ai_messages');
    }

    /**
     * Rétablit uniquement la structure historique en cas de retour arrière.
     * Les anciens messages supprimés ne peuvent pas être reconstitués.
     */
    public function down(): void
    {
        Schema::create('ai_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->string('provider', 40)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }
};
