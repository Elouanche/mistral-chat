<?php


function coDB() {  
    $serveur = get_env_variable('DB_HOST');
    $utilisateur = get_env_variable('DB_USER');
    $mot_de_passe = get_env_variable('DB_PASSWORD');
    $base_de_donnees = get_env_variable('DB_NAME');
    
    $conn = new mysqli($serveur, $utilisateur, $mot_de_passe, $base_de_donnees);
    
    if ($conn->connect_error) {
        logError('Erreur de connexion à la base de données', ['error' => $conn->connect_error]);
        echo json_encode(["error" =>'Erreur de connexion à la base de données : ' . $conn->connect_error]);
        return null;
    }
    
    logDebug('Connexion à la base de données établie');
    
    // Register shutdown function to log database disconnection
    register_shutdown_function(function() use ($conn) {
        if ($conn instanceof mysqli && !$conn->connect_error) {
            logDebug('Fermeture de la connexion à la base de données');
        }
    });
    
    return $conn;
}
?>