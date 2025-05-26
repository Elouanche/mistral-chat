<?php

/**
 * Classe CRUD pour la gestion des requêtes API envoyées à l'IA
 */
class AiRequestsCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'ai_requests', 'id');
    }
    
    
}