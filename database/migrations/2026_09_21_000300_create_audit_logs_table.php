<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Crée un journal immuable des opérations sensibles effectuées dans ABIWEIN. */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id()->comment('Identifiant interne de la trace');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete()->comment('Compte ayant effectué l’action');
            $table->string('actor_name', 150)->nullable()->comment('Nom de l’acteur conservé même si son compte disparaît');
            $table->string('event', 20)->index()->comment('Création, modification ou suppression');
            $table->string('auditable_type', 180)->comment('Classe du modèle concerné');
            $table->unsignedBigInteger('auditable_id')->comment('Identifiant de l’enregistrement concerné');
            $table->string('auditable_label', 180)->nullable()->comment('Libellé lisible conservé avec la trace');
            $table->json('old_values')->nullable()->comment('Valeurs sûres avant l’action');
            $table->json('new_values')->nullable()->comment('Valeurs sûres après l’action');
            $table->string('ip_address', 45)->nullable()->comment('Adresse IP à l’origine de la requête');
            $table->string('user_agent', 1024)->nullable()->comment('Navigateur ou client ayant envoyé la requête');
            $table->timestamp('created_at')->useCurrent()->index()->comment('Date et heure de l’action');

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['actor_id', 'created_at']);
        });
    }

    /** Supprime uniquement la table du journal. */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
