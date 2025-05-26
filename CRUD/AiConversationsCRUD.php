<?php

/**
 * Classe CRUD pour la gestion des conversations avec l'IA
 */
class AiConversationsCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'ai_conversations', 'id');
    }
    
  
}