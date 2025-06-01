<?php
require_once __DIR__ . '/BaseCRUD.php';
/**
 * Classe CRUD pour la gestion des conversations avec l'IA
 */
/*
CREATE TABLE IF NOT EXISTS ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(191) DEFAULT 'Nouvelle conversation',
    description TEXT,
    model_id INT NOT NULL,
    system_prompt TEXT,
    is_archived BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES ai_models(id) ON DELETE RESTRICT
) ENGINE = InnoDB;
*/

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