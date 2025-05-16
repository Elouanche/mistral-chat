<?php

if (!defined('BASE_PATH')) {
    die("Erreur critique : BASE_PATH n'est pas défini");
}

// Vérification des chemins critiques
$criticalPaths = [
    'vendor' => BASE_PATH . '/vendor',
    'autoload' => BASE_PATH . '/vendor/autoload.php',
    'env_file' => BASE_PATH . '/.env'
];

foreach ($criticalPaths as $name => $path) {
    if (!file_exists($path)) {
        logError("Chemin critique non trouvé : $name ($path)");
        die("Erreur critique : impossible de trouver $name");
    }
}

require_once $criticalPaths['autoload'];
use Dotenv\Dotenv;

function init_env(): void {
    global $criticalPaths;
    try {
        $dotenv = Dotenv::createImmutable(BASE_PATH);
        $dotenv->load();
        
        // Ajout d'une vérification après le chargement
        if (empty($_ENV)) {
            throw new \Exception("Aucune variable d'environnement n'a été chargée");
        }
    } catch (\Exception $e) {
        logError("Erreur lors du chargement du fichier .env : " . $e->getMessage());
        die("Impossible de charger les variables d'environnement");
    }
}

function get_env_variable(string $key, string $default = ''): string {
    $value = $_ENV[$key] ?? getenv($key) ?? $default;
    if ($value === '') {
        logError("Variable d'environnement '$key' non définie ou vide");
    }
    return $value;
}

// Appel au démarrage de l'app
init_env();
