<?php
require_once __DIR__ . '/BaseCRUD.php';
/**
 * Classe CRUD pour la gestion des plans d'abonnement
 */
/*

CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    max_conversations INT NOT NULL DEFAULT 3,
    max_messages_per_day INT NOT NULL DEFAULT 50,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
*/
class SubscriptionPlansCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'subscription_plans', 'id');
    }
    
   
}