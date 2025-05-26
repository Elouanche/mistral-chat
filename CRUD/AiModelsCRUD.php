<?php

/**
 * Classe CRUD pour la gestion des modèles d'IA
 */
class AiModelsCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'ai_models', 'id');
    }
    
    
}