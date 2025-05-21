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
    
    /**
     * Récupère toutes les conversations d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param bool $includeArchived Inclure les conversations archivées
     * @return array Liste des conversations
     */
    public function getUserConversations($userId, $includeArchived = false) {
        $filters = ['user_id' => $userId];
        
        if (!$includeArchived) {
            $filters['is_archived'] = 0;
        }
        
        $options = ['order_by' => ['updated_at' => 'DESC']];
        
        return $this->get(['*'], $filters, $options);
    }
    
    /**
     * Récupère une conversation spécifique
     * 
     * @param int $conversationId ID de la conversation
     * @param int|null $userId ID de l'utilisateur (pour vérification d'accès)
     * @return array|null Données de la conversation ou null si non trouvée/non autorisée
     */
    public function getConversation($conversationId, $userId = null) {
        $filters = ['id' => $conversationId];
        
        // Si un ID utilisateur est fourni, vérifier que la conversation lui appartient
        if ($userId !== null) {
            $filters['user_id'] = $userId;
        }
        
        $results = $this->get(['*'], $filters);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Crée une nouvelle conversation
     * 
     * @param array $conversationData Données de la conversation
     * @return int|bool ID de la conversation créée ou false en cas d'échec
     */
    public function createConversation($conversationData) {
        return $this->insert($conversationData);
    }
    
    /**
     * Met à jour une conversation
     * 
     * @param int $conversationId ID de la conversation
     * @param array $updateData Données à mettre à jour
     * @param int|null $userId ID de l'utilisateur (pour vérification d'accès)
     * @return bool Succès de l'opération
     */
    public function updateConversation($conversationId, $updateData, $userId = null) {
        // Vérifier que la conversation existe et appartient à l'utilisateur si spécifié
        if ($userId !== null) {
            $conversation = $this->getConversation($conversationId, $userId);
            if (!$conversation) {
                return false;
            }
        }
        
        return $this->update($conversationId, $updateData);
    }
    
    /**
     * Archive ou désarchive une conversation
     * 
     * @param int $conversationId ID de la conversation
     * @param bool $archive True pour archiver, False pour désarchiver
     * @param int|null $userId ID de l'utilisateur (pour vérification d'accès)
     * @return bool Succès de l'opération
     */
    public function archiveConversation($conversationId, $archive = true, $userId = null) {
        return $this->updateConversation($conversationId, ['is_archived' => $archive ? 1 : 0], $userId);
    }
    
    /**
     * Supprime une conversation
     * 
     * @param int $conversationId ID de la conversation
     * @param int|null $userId ID de l'utilisateur (pour vérification d'accès)
     * @return bool Succès de l'opération
     */
    public function deleteConversation($conversationId, $userId = null) {
        // Vérifier que la conversation existe et appartient à l'utilisateur si spécifié
        if ($userId !== null) {
            $conversation = $this->getConversation($conversationId, $userId);
            if (!$conversation) {
                return false;
            }
        }
        
        return $this->delete($conversationId);
    }
}