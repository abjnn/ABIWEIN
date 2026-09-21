<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Renforce l’unicité des reçus au niveau de la base de données. */
return new class extends Migration
{
    /** Empêche deux encaissements concurrents de recevoir la même référence. */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->unique('reference', 'payments_reference_unique');
        });
    }

    /** Retire uniquement l’index ajouté par cette migration. */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_reference_unique');
        });
    }
};
