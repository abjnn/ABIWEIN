<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ajoute la preuve d'exécution d'une recommandation sans altérer le diagnostic vétérinaire. */
    public function up(): void
    {
        Schema::table('veterinary_visits', function (Blueprint $table): void {
            $table->string('recommendation_status', 20)->default('pending')->index()->after('recommendations')->comment('État pending ou applied de la recommandation');
            $table->timestamp('recommendation_applied_at')->nullable()->after('recommendation_status')->comment('Moment de validation de l’action dans la ferme');
            $table->foreignId('recommendation_applied_by')->nullable()->after('recommendation_applied_at')->constrained('users')->nullOnDelete()->comment('Responsable ayant confirmé l’exécution');
        });
    }

    /** Retire uniquement les champs ajoutés par cette évolution. */
    public function down(): void
    {
        Schema::table('veterinary_visits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('recommendation_applied_by');
            $table->dropColumn(['recommendation_status', 'recommendation_applied_at']);
        });
    }
};
