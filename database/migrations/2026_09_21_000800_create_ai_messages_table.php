<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Crée un historique privé distinct pour chaque utilisateur autorisé. */
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table): void {
            // La clé primaire identifie chaque tour de conversation.
            $table->id();
            // La suppression d'un compte efface également son historique privé.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Seuls les messages de l'utilisateur et de l'assistant sont conservés.
            $table->enum('role', ['user', 'assistant']);
            // Le texte est limité par le formulaire avant son enregistrement.
            $table->text('content');
            // Le fournisseur distingue une réponse Gemini du repli local.
            $table->string('provider', 40)->nullable();
            $table->timestamps();

            // Cet index accélère le chargement chronologique d'une conversation.
            $table->index(['user_id', 'created_at']);
        });
    }

    /** Supprime uniquement la table créée par cette migration. */
    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
