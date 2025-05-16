<?php
require_once CRUD_PATH . '/SupportConversationCRUD.php';
require_once CRUD_PATH . '/SupportMessageCRUD.php';

/**
 * Service de gestion du support client
 * Utilise SupportConversationCRUD et SupportMessageCRUD pour les opérations de base de données
 */
class SupportService {
    /** @var SupportConversationCRUD $conversationCRUD Instance du CRUD conversations */
    private $conversationCRUD;
    
    /** @var SupportMessageCRUD $messageCRUD Instance du CRUD messages */
    private $messageCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->conversationCRUD = new SupportConversationCRUD($mysqli);
        $this->messageCRUD = new SupportMessageCRUD($mysqli);
    }
    
    /**
     * Crée une nouvelle conversation de support
     * 
     * @param array $data Données de la conversation (user_id, subject, message)
     * @return array Statut de l'opération
     */
    public function createConversation($data) {
        logInfo("Creating support ticket", [
            'user_id' => $data['user_id'] ?? null,
            'subject' => $data['subject'] ?? null
        ]);
        
        $userId = $data['user_id'] ?? null;
        $subject = $data['subject'] ?? null;
        $message = $data['message'] ?? null;
        
        if (!$userId || !$subject || !$message) {
            return ['status' => 'error', 'message' => 'User ID, subject and message are required'];
        }
        
        // Démarrer une transaction
        $this->conversationCRUD->beginTransaction();
        
        try {
            // Création de la conversation
            $conversationData = [
                'user_id' => $userId,
                'subject' => $subject,
                'status' => 'Open',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $conversationId = $this->conversationCRUD->insert($conversationData);
            
            if (!$conversationId) {
                throw new Exception('Failed to create conversation');
            }
            
            // Ajout du premier message
            $messageData = [
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'message' => $message,
                'is_from_admin' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $messageId = $this->messageCRUD->insert($messageData);
            
            if (!$messageId) {
                throw new Exception('Failed to add message');
            }
            
            // Valider la transaction
            $this->conversationCRUD->commit();
            
            // Notification au service de notification
            return [
                'status' => 'pending',
                'service' => 'Notification',
                'action' => 'sendEmail',
                'data' => [
                    'message' => 'Support request created',
                    'email_data' => [
                        'type' => 'support_request',
                        'to' => 'support@example.com',
                        'subject' => 'New Support Request: ' . $subject,
                        'conversation_id' => $conversationId,
                        'user_id' => $userId
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->conversationCRUD->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Ajoute un message à une conversation existante
     * 
     * @param array $data Données du message (conversation_id, user_id, message, is_from_admin)
     * @return array Statut de l'opération
     */
    public function addMessage($data) {
        logInfo("Adding message to support ticket", [
            'conversation_id' => $data['conversation_id'] ?? null,
            'user_id' => $data['user_id'] ?? null
        ]);
        
        $conversationId = $data['conversation_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $message = $data['message'] ?? null;
        $isFromAdmin = $data['is_from_admin'] ?? 0;
        
        if (!$conversationId || !$userId || !$message) {
            return ['status' => 'error', 'message' => 'Conversation ID, user ID and message are required'];
        }
        
        // Vérifier si la conversation existe
        $conversation = $this->conversationCRUD->find($conversationId);
        
        if (!$conversation) {
            return ['status' => 'error', 'message' => 'Conversation not found'];
        }
        
        // Ajout du message
        $messageData = [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'message' => $message,
            'is_from_admin' => $isFromAdmin ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $messageId = $this->messageCRUD->insert($messageData);
        
        if (!$messageId) {
            return ['status' => 'error', 'message' => 'Failed to add message'];
        }
        
        // Mise à jour de la date de dernière modification de la conversation
        $this->conversationCRUD->update(
            ['updated_at' => date('Y-m-d H:i:s')],
            ['id' => $conversationId]
        );
        
        // Si le message est d'un administrateur, notifier l'utilisateur
        if ($isFromAdmin) {
            return [
                'status' => 'pending',
                'service' => 'Notification',
                'action' => 'sendEmail',
                'data' => [
                    'message' => 'Support response added',
                    'email_data' => [
                        'type' => 'support_response',
                        'user_id' => $conversation['user_id'],
                        'subject' => 'Response to your support request: ' . $conversation['subject'],
                        'conversation_id' => $conversationId,
                        'message' => $message
                    ]
                ]
            ];
        }
        
        return ['status' => 'success', 'message' => 'Message added successfully'];
    }

    /**
     * Crée un ticket de support
     * 
     * @param array $data Données du ticket
     */
    public function createTicket($data) {
        logInfo("Creating support ticket", [
            'user_id' => $data['user_id'] ?? null,
            'subject' => $data['subject'] ?? null
        ]);
        // Implementation here
    }

    /**
     * Met à jour un ticket de support
     * 
     * @param array $data Données du ticket
     */
    public function updateTicket($data) {
        logInfo("Updating support ticket", [
            'ticket_id' => $data['ticket_id'] ?? null,
            'status' => $data['status'] ?? null
        ]);
        // Implementation here
    }

    /**
     * Récupère les détails d'un ticket de support
     * 
     * @param array $data Données du ticket
     */
    public function getTicket($data) {
        logInfo("Getting ticket details", ['ticket_id' => $data['ticket_id'] ?? null]);
        // Implementation here
    }

    /**
     * Liste les tickets de support
     * 
     * @param array $data Données de pagination
     */
    public function listTickets($data) {
        logInfo("Listing support tickets", [
            'page' => $data['page'] ?? 1,
            'limit' => $data['limit'] ?? 10
        ]);
        // Implementation here
    }

    /**
     * Assigne un ticket de support à un agent
     * 
     * @param array $data Données du ticket et de l'agent
     */
    public function assignTicket($data) {
        logInfo("Assigning support ticket", [
            'ticket_id' => $data['ticket_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? null
        ]);
        // Implementation here
    }
}
?>