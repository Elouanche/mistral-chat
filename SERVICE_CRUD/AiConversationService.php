<?php
require_once CRUD_PATH . '/AiConversationsCRUD.php';
require_once CRUD_PATH . '/AiMessagesCRUD.php';
require_once CRUD_PATH . '/AiModelsCRUD.php';

/**
 * Service pour la gestion des conversations avec l'IA
 */
class AiConversationService {
    /** @var AiConversationsCRUD $conversationsCRUD Instance du CRUD pour les conversations */
    private $conversationsCRUD;
    
    /** @var AiMessagesCRUD $messagesCRUD Instance du CRUD pour les messages */
    private $messagesCRUD;
    
    /** @var AiModelsCRUD $modelsCRUD Instance du CRUD pour les modèles */
    private $modelsCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->conversationsCRUD = new AiConversationsCRUD($mysqli);
        $this->messagesCRUD = new AiMessagesCRUD($mysqli);
        $this->modelsCRUD = new AiModelsCRUD($mysqli);
    }
    
    /**
     * Récupère toutes les conversations d'un utilisateur
     * 
     * @param array $data Données avec l'ID de l'utilisateur
     * @return array Résultat de l'opération
     */
    public function getUserConversations($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['user_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur est obligatoire"
                ];
            }
            
            $includeArchived = isset($data['include_archived']) ? (bool)$data['include_archived'] : false;
           
             $filters = ['user_id' => $data['user_id']];
        
            if (!$includeArchived) {
                $filters['is_archived'] = 0;
            }
            
            $options = ['order_by' => ['updated_at' => 'DESC']];
            
            $conversations =  $this->conversationsCRUD->get(['*'], $filters, $options);
            
            // Ajouter le dernier message pour chaque conversation
            foreach ($conversations as &$conversation) {
                $lastMessage = $this->messagesCRUD->getLastMessage($conversation['id']);
                $conversation['last_message'] = $lastMessage;
                
                // Compter le nombre de messages
                $conversationId = $data['conversation_id'];
                
                // Mettre à jour ou créer le message système
               
                $filters = ['conversation_id' => $conversationId];
                $options = ['order_by' => ['created_at' => 'ASC']];
                
                $messages =  $this->messagesCRUD->get(['*'], $filters, $options);
                $conversation['message_count'] = count($messages);
            }
            
            return [
                'status' => 'success',
                'data' => $conversations
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la récupération des conversations", ['error' => $e->getMessage(), 'user_id' => $data['user_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la récupération des conversations"
            ];
        }
    }
    
    /**
     * Récupère une conversation spécifique avec ses messages
     * 
     * @param array $data Données avec l'ID de la conversation et l'ID de l'utilisateur
     * @return array Résultat de l'opération
     */
    public function getConversation($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['conversation_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de la conversation est obligatoire"
                ];
            }
            $conversationId = $data['conversation_id'];
            
            $userId = $data['user_id'] ?? null;
           
             $filters = ['id' => $conversationId];
        
            // Si un ID utilisateur est fourni, vérifier que la conversation lui appartient
            if ($userId !== null) {
                $filters['user_id'] = $userId;
            }
            
            $results = $this->conversationsCRUD->get(['*'], $filters);
            $conversation = !empty($results) ? $results[0] : null;
            if (!$conversation) {
                return [
                    'status' => 'error',
                    'message' => "Conversation non trouvée ou non autorisée"
                ];
            }
            
            // Récupérer les messages de la conversation
            $conversationId = $data['conversation_id'];
                
                // Mettre à jour ou créer le message système
               
            $filters = ['conversation_id' => $conversationId];
            $options = ['order_by' => ['created_at' => 'ASC']];
                
            $messages =  $this->messagesCRUD->get(['*'], $filters, $options);
            $conversation['messages'] = $messages;
            
            // Récupérer les informations du modèle
            $model = $this->modelsCRUD->get(['*'], ['id' => $conversation['model_id']]);
            $conversation['model'] = !empty($model) ? $model[0] : null;
            
            return [
                'status' => 'success',
                'data' => $conversation
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la récupération de la conversation", ['error' => $e->getMessage(), 'conversation_id' => $data['conversation_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la récupération de la conversation"
            ];
        }
    }
    
    /**
     * Crée une nouvelle conversation
     * 
     * @param array $data Données de la conversation
     * @return array Résultat de l'opération
     */
    public function createConversation($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['user_id']) || !isset($data['model_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur et l'ID du modèle sont obligatoires"
                ];
            }
            
            // Vérifier que le modèle existe et est actif
            $model = $this->modelsCRUD->get(['*'], ['id' => $data['model_id'], 'is_active' => 1]);
            if (empty($model)) {
                return [
                    'status' => 'error',
                    'message' => "Le modèle spécifié n'existe pas ou n'est pas actif"
                ];
            }
            
            // Préparer les données de la conversation
            $conversationData = [
                'user_id' => $data['user_id'],
                'model_id' => $data['model_id'],
                'title' => $data['title'] ?? 'Nouvelle conversation',
                'description' => $data['description'] ?? null,
                'system_prompt' => $data['system_prompt'] ?? null
            ];
            
            // Créer la conversation
            $conversationId = $this->conversationsCRUD->insert($conversationData);
            
            if (!$conversationId) {
                return [
                    'status' => 'error',
                    'message' => "Erreur lors de la création de la conversation"
                ];
            }
            
            // Ajouter un message système si un prompt système est fourni
            if (isset($data['system_prompt']) && !empty($data['system_prompt'])) {
                $systemMessage = [
                    'conversation_id' => $conversationId,
                    'role' => 'system',
                    'content' => $data['system_prompt']
                ];
                
                $this->messagesCRUD->addMessage($systemMessage);
            }
            
            return [
                'status' => 'success',
                'message' => "Conversation créée avec succès",
                'data' => ['conversation_id' => $conversationId]
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la création de la conversation", ['error' => $e->getMessage(), 'user_id' => $data['user_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la création de la conversation"
            ];
        }
    }
    
    /**
     * Met à jour une conversation
     * 
     * @param array $data Données de la conversation à mettre à jour
     * @return array Résultat de l'opération
     */
    public function updateConversation($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['conversation_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de la conversation est obligatoire"
                ];
            }
            
            $userId = $data['user_id'] ?? null;
            
            // Préparer les données à mettre à jour
            $updateData = [];
            
            if (isset($data['title'])) {
                $updateData['title'] = $data['title'];
            }
            
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            
            if (isset($data['system_prompt'])) {
                $updateData['system_prompt'] = $data['system_prompt'];
                $conversationId = $data['conversation_id'];
                
                // Mettre à jour ou créer le message système
               
                $filters = ['conversation_id' => $conversationId];
                $options = ['order_by' => ['created_at' => 'ASC']];
                
                 $messages =  $this->messagesCRUD->get(['*'], $filters, $options);
                $systemMessageExists = false;
                
                foreach ($messages as $message) {
                    if ($message['role'] === 'system') {
                        $this->messagesCRUD->updateMessage($message['id'], ['content' => $data['system_prompt']]);
                        $systemMessageExists = true;
                        break;
                    }
                }
                
                if (!$systemMessageExists) {
                    $systemMessage = [
                        'conversation_id' => $data['conversation_id'],
                        'role' => 'system',
                        'content' => $data['system_prompt']
                    ];
                    
                    $this->messagesCRUD->addMessage($systemMessage);
                }
            }
            
            if (isset($data['is_archived'])) {
                $updateData['is_archived'] = (bool)$data['is_archived'] ? 1 : 0;
            }
            
            // Si aucune donnée à mettre à jour, retourner une erreur
            if (empty($updateData)) {
                return [
                    'status' => 'error',
                    'message' => "Aucune donnée à mettre à jour"
                ];
            }
            
            // Mettre à jour la conversation
           $success = $this->conversationsCRUD->update($updateData, ['id' => $data['conversation_id']]);
            
            if (!$success) {
                return [
                    'status' => 'error',
                    'message' => "Erreur lors de la mise à jour de la conversation"
                ];
            }
            
            return [
                'status' => 'success',
                'message' => "Conversation mise à jour avec succès"
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la mise à jour de la conversation", ['error' => $e->getMessage(), 'conversation_id' => $data['conversation_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la mise à jour de la conversation"
            ];
        }
    }
    
    /**
     * Archive ou désarchive une conversation
     * 
     * @param array $data Données avec l'ID de la conversation et l'état d'archivage
     * @return array Résultat de l'opération
     */
    public function archiveConversation($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['conversation_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de la conversation est obligatoire"
                ];
            }
            
            $userId = $data['user_id'] ?? null;
            $archive = isset($data['archive']) ? (bool)$data['archive'] : true;
            
            // Archiver/désarchiver la conversation
            $success = $this->conversationsCRUD->archiveConversation($data['conversation_id'], $archive, $userId);
            
            if (!$success) {
                return [
                    'status' => 'error',
                    'message' => "Erreur lors de l'archivage de la conversation"
                ];
            }
            
            return [
                'status' => 'success',
                'message' => $archive ? "Conversation archivée avec succès" : "Conversation désarchivée avec succès"
            ];
        } catch (Exception $e) {
            logError("Erreur lors de l'archivage de la conversation", ['error' => $e->getMessage(), 'conversation_id' => $data['conversation_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de l'archivage de la conversation"
            ];
        }
    }
    
    /**
     * Supprime une conversation et tous ses messages
     * 
     * @param array $data Données avec l'ID de la conversation
     * @return array Résultat de l'opération
     */
    public function deleteConversation($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['conversation_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de la conversation est obligatoire"
                ];
            }
            
            $userId = $data['user_id'] ?? null;
            
            // Supprimer d'abord tous les messages de la conversation
            $this->messagesCRUD->deleteConversationMessages($data['conversation_id']);
            
            // Supprimer la conversation
            $success = $this->conversationsCRUD->deleteConversation($data['conversation_id'], $userId);
            
            if (!$success) {
                return [
                    'status' => 'error',
                    'message' => "Erreur lors de la suppression de la conversation"
                ];
            }
            
            return [
                'status' => 'success',
                'message' => "Conversation supprimée avec succès"
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la suppression de la conversation", ['error' => $e->getMessage(), 'conversation_id' => $data['conversation_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la suppression de la conversation"
            ];
        }
    }
}