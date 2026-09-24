<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée le socle relationnel des modules ABIWEIN.
     * Chaque colonne comporte un commentaire métier pour faciliter les futures modifications.
     */
    public function up(): void
    {
        // FOURNISSEURS : personnes ou sociétés qui approvisionnent la ferme.
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id()->comment('Identifiant interne du fournisseur');
            $table->string('name', 150)->index()->comment('Nom commercial ou nom complet');
            $table->string('contact_person', 120)->nullable()->comment('Interlocuteur principal');
            $table->string('phone', 30)->nullable()->comment('Numéro de téléphone');
            $table->string('email', 150)->nullable()->comment('Adresse électronique');
            $table->text('address')->nullable()->comment('Adresse physique');
            $table->boolean('is_active')->default(true)->index()->comment('Fournisseur encore utilisable');
            $table->timestamps();
        });

        // BÂTIMENTS : lieux physiques de l’exploitation et leur capacité.
        Schema::create('buildings', function (Blueprint $table): void {
            $table->id()->comment('Identifiant interne du bâtiment');
            $table->string('code', 30)->unique()->comment('Code court affiché dans l’application');
            $table->string('name', 120)->comment('Nom lisible du bâtiment');
            $table->string('type', 30)->index()->comment('Chauffage, élevage, froid ou stock');
            $table->unsignedInteger('capacity')->default(0)->comment('Capacité maximale en sujets ou unités');
            $table->string('status', 30)->default('active')->index()->comment('État actuel du bâtiment');
            $table->text('notes')->nullable()->comment('Informations complémentaires');
            $table->timestamps();
        });

        // BANDES : groupes de poulets suivis ensemble pendant un cycle d’élevage.
        Schema::create('flocks', function (Blueprint $table): void {
            $table->id()->comment('Identifiant interne de la bande');
            $table->string('code', 40)->unique()->comment('Référence unique de la bande');
            $table->string('breed', 100)->comment('Souche ou race des poulets');
            $table->date('arrival_date')->index()->comment('Date d’arrivée des poussins');
            $table->date('expected_end_date')->nullable()->comment('Date prévisionnelle de fin de cycle');
            $table->unsignedInteger('initial_quantity')->comment('Effectif reçu au démarrage');
            $table->unsignedInteger('current_quantity')->comment('Effectif vivant ou disponible actuel');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete()->comment('Fournisseur d’origine');
            $table->foreignId('current_building_id')->nullable()->constrained('buildings')->nullOnDelete()->comment('Bâtiment actuellement affecté');
            $table->string('status', 30)->default('active')->index()->comment('Bande active, clôturée ou archivée');
            $table->timestamp('closed_at')->nullable()->comment('Moment de clôture définitive');
            $table->text('notes')->nullable()->comment('Observations générales');
            $table->timestamps();
        });

        // AFFECTATIONS : historique des périodes passées par une bande dans chaque bâtiment.
        Schema::create('flock_building_assignments', function (Blueprint $table): void {
            $table->id()->comment('Identifiant de l’affectation');
            $table->foreignId('flock_id')->constrained()->cascadeOnDelete()->comment('Bande concernée');
            $table->foreignId('building_id')->constrained()->restrictOnDelete()->comment('Bâtiment affecté');
            $table->timestamp('started_at')->index()->comment('Début de l’affectation');
            $table->timestamp('ended_at')->nullable()->comment('Fin de l’affectation');
            $table->unsignedInteger('quantity')->comment('Effectif placé dans le bâtiment');
            $table->text('notes')->nullable()->comment('Motif ou précision sur l’affectation');
            $table->timestamps();
            $table->index(['flock_id', 'started_at']);
        });

        // MOUVEMENTS D’EFFECTIF : entrées, transferts, mortalités, ventes et ajustements.
        Schema::create('flock_movements', function (Blueprint $table): void {
            $table->id()->comment('Identifiant du mouvement');
            $table->foreignId('flock_id')->constrained()->cascadeOnDelete()->comment('Bande concernée');
            $table->string('type', 30)->index()->comment('Nature du mouvement d’effectif');
            $table->foreignId('from_building_id')->nullable()->constrained('buildings')->nullOnDelete()->comment('Bâtiment de départ');
            $table->foreignId('to_building_id')->nullable()->constrained('buildings')->nullOnDelete()->comment('Bâtiment d’arrivée');
            $table->unsignedInteger('quantity')->comment('Nombre de sujets concernés');
            $table->timestamp('occurred_at')->index()->comment('Date et heure du mouvement');
            $table->string('reason', 255)->nullable()->comment('Motif lisible du mouvement');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete()->comment('Utilisateur ayant enregistré le mouvement');
            $table->timestamps();
        });

        // SUIVIS QUOTIDIENS : mesures techniques saisies une fois par jour et par bande.
        Schema::create('daily_records', function (Blueprint $table): void {
            $table->id()->comment('Identifiant du relevé quotidien');
            $table->foreignId('flock_id')->constrained()->cascadeOnDelete()->comment('Bande observée');
            $table->date('record_date')->index()->comment('Journée concernée par le relevé');
            $table->unsignedInteger('mortality')->default(0)->comment('Nombre de morts constatées');
            $table->decimal('feed_kg', 10, 2)->default(0)->comment('Aliment consommé en kilogrammes');
            $table->decimal('water_liters', 10, 2)->default(0)->comment('Eau consommée en litres');
            $table->decimal('average_weight_kg', 8, 3)->nullable()->comment('Poids moyen en kilogrammes');
            $table->string('health_status', 30)->default('good')->index()->comment('État sanitaire observé');
            $table->text('observations')->nullable()->comment('Notes prises pendant la visite');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete()->comment('Utilisateur ayant saisi le relevé');
            $table->timestamps();
            $table->unique(['flock_id', 'record_date']);
        });

        // PRODUITS : intrants de la ferme (aliments, produits sanitaires et équipements), jamais les ventes de poulets.
        Schema::create('products', function (Blueprint $table): void {
            $table->id()->comment('Identifiant interne du produit');
            $table->string('code', 40)->unique()->comment('Référence unique du produit');
            $table->string('name', 150)->index()->comment('Nom affiché du produit');
            $table->string('category', 40)->index()->comment('Famille fonctionnelle du produit');
            $table->string('unit', 20)->comment('Unité de gestion du stock');
            $table->decimal('current_stock', 12, 2)->default(0)->comment('Quantité actuellement disponible');
            $table->decimal('alert_threshold', 12, 2)->default(0)->comment('Seuil déclenchant une alerte interne');
            $table->date('expires_at')->nullable()->index()->comment('Date d’expiration si applicable');
            $table->boolean('is_active')->default(true)->index()->comment('Produit encore utilisable');
            $table->timestamps();
        });

        // MOUVEMENTS DE STOCK : journal immuable des entrées, sorties et corrections.
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id()->comment('Identifiant du mouvement de stock');
            $table->foreignId('product_id')->constrained()->restrictOnDelete()->comment('Produit concerné');
            $table->string('type', 30)->index()->comment('Entrée, sortie ou ajustement');
            $table->decimal('quantity', 12, 2)->comment('Quantité positive du mouvement');
            $table->timestamp('occurred_at')->index()->comment('Date et heure du mouvement');
            $table->string('reason', 255)->comment('Justification du mouvement');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete()->comment('Utilisateur ayant enregistré le mouvement');
            $table->timestamps();
        });

        // CLIENTS : acheteurs particuliers ou professionnels.
        Schema::create('clients', function (Blueprint $table): void {
            $table->id()->comment('Identifiant interne du client');
            $table->string('name', 150)->index()->comment('Nom complet ou raison sociale');
            $table->string('type', 30)->default('individual')->index()->comment('Particulier ou entreprise');
            $table->string('phone', 30)->nullable()->comment('Numéro de téléphone');
            $table->string('email', 150)->nullable()->comment('Adresse électronique');
            $table->text('address')->nullable()->comment('Adresse de livraison ou facturation');
            $table->boolean('is_active')->default(true)->index()->comment('Client encore actif');
            $table->timestamps();
        });

        // VENTES : ventes au comptant ou à crédit, réglées uniquement en espèces.
        Schema::create('sales', function (Blueprint $table): void {
            $table->id()->comment('Identifiant interne de la vente');
            $table->string('reference', 40)->unique()->comment('Référence lisible de la vente');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete()->comment('Client concerné');
            $table->date('sale_date')->index()->comment('Date de conclusion de la vente');
            $table->string('sale_type', 20)->index()->comment('Vente au comptant ou à crédit');
            $table->string('payment_method', 20)->default('cash')->comment('Moyen de paiement, espèces uniquement');
            $table->decimal('total_amount', 12, 2)->comment('Montant total de la vente');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('Somme déjà réglée en espèces');
            $table->decimal('balance_due', 12, 2)->default(0)->comment('Solde restant à payer');
            $table->string('status', 30)->default('unpaid')->index()->comment('État financier de la vente');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete()->comment('Utilisateur ayant créé la vente');
            $table->timestamps();
        });

        // LIGNES DE VENTE : détail des produits et quantités vendus.
        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id()->comment('Identifiant de la ligne de vente');
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete()->comment('Vente parente');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete()->comment('Produit référencé si disponible');
            $table->string('description', 180)->comment('Libellé conservé au moment de la vente');
            $table->decimal('quantity', 12, 2)->comment('Quantité vendue');
            $table->decimal('unit_price', 12, 2)->comment('Prix unitaire appliqué');
            $table->decimal('line_total', 12, 2)->comment('Total de la ligne');
            $table->timestamps();
        });

        // PAIEMENTS : versements en espèces liés aux ventes à crédit ou au comptant.
        Schema::create('payments', function (Blueprint $table): void {
            $table->id()->comment('Identifiant du paiement');
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete()->comment('Vente réglée');
            $table->decimal('amount', 12, 2)->comment('Montant versé en espèces');
            $table->timestamp('paid_at')->index()->comment('Date et heure du versement');
            $table->string('payment_method', 20)->default('cash')->comment('Espèces, seul moyen autorisé');
            $table->string('reference', 80)->nullable()->comment('Référence interne du reçu');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete()->comment('Utilisateur ayant saisi le paiement');
            $table->timestamps();
        });

        // DÉPENSES : sorties de caisse nécessaires au calcul du bilan.
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id()->comment('Identifiant de la dépense');
            $table->string('reference', 40)->unique()->comment('Référence interne de la dépense');
            $table->string('category', 80)->index()->comment('Catégorie comptable simplifiée');
            $table->string('description', 255)->comment('Motif détaillé de la dépense');
            $table->decimal('amount', 12, 2)->comment('Montant décaissé');
            $table->date('spent_on')->index()->comment('Date de la dépense');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete()->comment('Utilisateur ayant saisi la dépense');
            $table->timestamps();
        });

        // VISITES VÉTÉRINAIRES : observations et recommandations liées à une bande.
        Schema::create('veterinary_visits', function (Blueprint $table): void {
            $table->id()->comment('Identifiant de la visite');
            $table->foreignId('flock_id')->constrained()->cascadeOnDelete()->comment('Bande examinée');
            $table->foreignId('veterinarian_id')->constrained('users')->restrictOnDelete()->comment('Vétérinaire responsable');
            $table->date('visit_date')->index()->comment('Date de la visite');
            $table->text('diagnosis')->comment('Diagnostic ou constat clinique');
            $table->text('recommendations')->nullable()->comment('Mesures recommandées');
            $table->date('next_visit_date')->nullable()->index()->comment('Prochaine visite prévue');
            $table->timestamps();
        });

        // ALERTES : notifications internes provenant d’un seuil, d’une échéance ou d’une anomalie.
        Schema::create('alerts', function (Blueprint $table): void {
            $table->id()->comment('Identifiant de l’alerte');
            $table->string('type', 50)->index()->comment('Origine fonctionnelle de l’alerte');
            $table->string('severity', 20)->default('info')->index()->comment('Niveau info, attention ou critique');
            $table->string('title', 180)->comment('Titre court affiché dans l’interface');
            $table->text('message')->comment('Explication détaillée de l’alerte');
            $table->nullableMorphs('source');
            $table->string('status', 30)->default('open')->index()->comment('État ouvert, acquitté ou résolu');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete()->comment('Utilisateur ayant pris connaissance');
            $table->timestamp('acknowledged_at')->nullable()->comment('Date d’acquittement');
            $table->timestamp('resolved_at')->nullable()->comment('Date de résolution');
            $table->timestamps();
        });
    }

    /** Supprime les tables dans l’ordre inverse des dépendances. */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('veterinary_visits');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('products');
        Schema::dropIfExists('daily_records');
        Schema::dropIfExists('flock_movements');
        Schema::dropIfExists('flock_building_assignments');
        Schema::dropIfExists('flocks');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('suppliers');
    }
};
