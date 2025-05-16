<?php

// Fonction pour la connexion à la base de données
function coDB() {  
    require_once __DIR__ . "/credentials.php";
    
    $conn = new mysqli($serveur, $utilisateur, $mot_de_passe, $base_de_donnees);
    
    if ($conn->connect_error) {
        echo json_encode(["error" =>'Erreur de connexion à la base de données : ' . $conn->connect_error]);
        return null;
    }
    
    return $conn;
}

// Fonction pour tester les tables dans la base de données
function testDatabaseTables() {
    // Connexion à la base de données
    $conn = coDB();

    if (!$conn) {
        return [
            'success' => false,
            'reason' => "Erreur lors de la connexion à la base de données.",
        ];
    }

    // Liste des tables à tester
    $tables = [
        'users',
        'products',
        'cart',
        'cart_items',
        'orders',
        'order_items',
        'returns',
        'support_conversations',
        'support_messages',
        'reviews',
        'review_responses',
        'payments',
        'notifications',
        'deliveries',
        'error_logs',
        'monitoring',
        'analytics',
    ];

    // Test de chaque table
    foreach ($tables as $table) {
        $query = "SELECT COUNT(*) FROM $table";
        $result = $conn->query($query);

        // Si une erreur survient pour cette table, on retourne un échec avec la raison
        if (!$result) {
            return [
                'success' => false,
                'reason' => "Erreur lors de l'exécution de la requête sur la table '$table'. Erreur SQL : " . $conn->error,
            ];
        }
    }

    // Fermeture de la connexion à la base de données
    $conn->close();

    // Si toutes les tables ont été testées sans erreur, retourner un succès
    return [
        'success' => true
    ];
}

// Lancer le test de la base de données et retourner le résultat
return testDatabaseTables();
?>