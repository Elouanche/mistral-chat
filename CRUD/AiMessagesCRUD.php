<?php

/**
 * Classe CRUD pour la gestion des messages dans les conversations avec l'IA
 */
class AiMessagesCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'ai_messages', 'id');
    }
    
    
}