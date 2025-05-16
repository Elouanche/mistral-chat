<?php
// Ce fichier s'assure qu'une session est toujours initialisée

require_once CONFIG_PATH . "log_config.php";
require_once SHARED_PATH . "erreur.php";

// Utiliser la fonction verifySession sans paramètres pour initialiser la session
function initSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        
        // Vérifier si la session est valide
        if (empty(session_id())) {
            logError("Impossible d'initialiser la session");
            return false;
        }
        
        return true;
    }
    
    return true;
}

// Initialiser la session
initSession();
?>