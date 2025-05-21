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
    
    /**
     * Récupère tous les messages d'une conversation
     * 
     * @param int $conversationId ID de la conversation
     * @return array Liste des messages
     */
    public function getConversationMessages($conversationId) {
        $filters = ['conversation_id' => $conversationId];
        $options = ['order_by' => ['created_at' => 'ASC']];
        
        return $this->get(['*'], $filters, $options);
    }
    
    /**
     * Récupère le dernier message d'une conversation
     * 
     * @param int $conversationId ID de la conversation
     * @return array|null Dernier message ou null si aucun message
     */
    public function getLastMessage($conversationId) {
        $filters = ['conversation_id' => $conversationId];
        $options = [
            'order_by' => ['created_at' => 'DESC'],
            'limit' => 1
        ];
        
        $results = $this->get(['*'], $filters, $options);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Ajoute un nouveau message à une conversation
     * 
     * @param array $messageData Données du message
     * @return int|bool ID du message créé ou false en cas d'échec
     */
    public function addMessage($messageData) {
        return $this->insert($messageData);
    }
    
    /**
     * Met à jour un message
     * 
     * @param int $messageId ID du message
     * @param array $updateData Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function updateMessage($messageId, $updateData) {
        return $this->update($messageId, $updateData);
    }
    
    /**
     * Supprime un message
     * 
     * @param int $messageId ID du message
     * @return bool Succès de l'opération
     */
    public function deleteMessage($messageId) {
        return $this->delete($messageId);
    }
    
    /**
     * Supprime tous les messages d'une conversation
     * 
     * @param int $conversationId ID de la conversation
     * @return bool Succès de l'opération
     */
    public function deleteConversationMessages($conversationId) {
        $query = "DELETE FROM `{$this->table}` WHERE `conversation_id` = ?";
        $stmt = $this->mysqli->prepare($query);
        
        if (!$stmt) {
            $this->errorInfo = ['message' => $this->mysqli->error];
            return false;
        }
        
        $stmt->bind_param('i', $conversationId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Compte le nombre de messages dans une conversation
     * 
     * @param int $conversationId ID de la conversation
     * @return int Nombre de messages
     */
    public function countMessages($conversationId) {
        $query = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE `conversation_id` = ?";
        $stmt = $this->mysqli->prepare($query);
        
        if (!$stmt) {
            $this->errorInfo = ['message' => $this->mysqli->error];
            return 0;
        }
        
        $stmt->bind_param('i', $conversationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] ?? 0;
    }
}