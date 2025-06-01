<?php
require_once __DIR__ . '/BaseCRUD.php';
/**
 * Classe CRUD pour la gestion des messages dans les conversations avec l'IA
 */
/*
CREATE TABLE IF NOT EXISTS ai_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content TEXT NOT NULL,
    tokens_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id) ON DELETE CASCADE
) ENGINE = InnoDB;
*/

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