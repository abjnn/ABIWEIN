<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/** Installe une seule fois les comptes et données nécessaires au site déployé. */
final class BootstrapDeployment extends Command
{
    /** Cette commande est exécutée au démarrage du conteneur après les migrations. */
    protected $signature = 'abiwein:bootstrap';

    /** Ce texte apparaît dans l'aide Artisan destinée au mainteneur. */
    protected $description = 'Initialise une base ABIWEIN vide avec le scénario autorisé par la configuration.';

    /** N'altère jamais une base qui contient déjà au moins un compte utilisateur. */
    public function handle(): int
    {
        if (! Schema::hasTable('users')) {
            $this->error('La table users est absente : exécutez les migrations avant l’initialisation.');

            return self::FAILURE;
        }

        if (User::query()->exists()) {
            $this->info('Base ABIWEIN déjà initialisée : aucune donnée n’a été modifiée.');

            return self::SUCCESS;
        }

        if (! config('abiwein.deployment_demo_enabled')) {
            $this->warn('Initialisation automatique désactivée par ABIWEIN_ENABLE_PRODUCTION_DEMO.');

            return self::SUCCESS;
        }

        // DatabaseSeeder applique les mêmes contrôles de sécurité avant d'insérer les données.
        $exitCode = $this->call('db:seed', ['--force' => true]);

        return $exitCode === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }
}
