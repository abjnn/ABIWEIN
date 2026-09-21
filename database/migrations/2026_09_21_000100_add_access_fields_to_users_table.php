<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les informations nécessaires aux rôles et au contrôle des comptes.
     * MODIFICATION : les valeurs autorisées des rôles sont centralisées dans UserRole.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Le rôle pilote les menus visibles et les autorisations serveur.
            $table->string('role', 30)->default('farm_manager')->after('password')->index()->comment('Rôle applicatif de l’utilisateur');

            // Un compte bloqué ne peut plus ouvrir ni conserver une session privée.
            $table->boolean('is_active')->default(true)->after('role')->index()->comment('Indique si le compte peut se connecter');

            // Cette date permet à l’administrateur de contrôler la dernière connexion réussie.
            $table->timestamp('last_login_at')->nullable()->after('is_active')->comment('Dernière connexion réussie');
        });
    }

    /** Retire uniquement les champs ajoutés par cette migration. */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });
    }
};
