<?php
require_once CRUD_PATH . 'NotificationsCRUD.php';

require_once CRUD_PATH . 'OrdersCRUD.php';
require_once BASE_PATH . 'vendor/autoload.php';
require_once CRUD_PATH . 'BaseCRUD.php';
require_once CRUD_PATH . 'UsersCRUD.php';
require_once CRUD_PATH . 'ProductsCRUD.php';
require_once CRUD_PATH . 'ProductsImageCRUD.php';
require_once CRUD_PATH . 'ReviewsCRUD.php';
require_once CRUD_PATH . 'ReviewResponsesCRUD.php';
require_once CRUD_PATH . 'OrdersCRUD.php';
require_once CRUD_PATH . 'OrderItemsCRUD.php';
require_once CRUD_PATH . 'CartCRUD.php';
require_once CRUD_PATH . 'CartItemsCRUD.php';
require_once CRUD_PATH . 'PaymentsCRUD.php';
require_once CRUD_PATH . 'DeliveriesCRUD.php';
require_once CRUD_PATH . 'ReturnedsCRUD.php';
require_once CRUD_PATH . 'AnalyticsCRUD.php';
require_once CRUD_PATH . 'NotificationsCRUD.php';
require_once CRUD_PATH . 'MonitoringCRUD.php';
require_once CRUD_PATH . 'ErrorLogsCRUD.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Service de gestion des notifications
 * Utilise NotificationsCRUD pour les opérations de base de données
 * Inclut l'envoi d'emails avec PHPMailer
 */
class NotificationService {
    /** @var NotificationsCRUD $notificationsCRUD Instance du CRUD notifications */
    private $notificationsCRUD;
    /** @var OrdersCRUD $ordersCRUD Instance du CRUD notifications */
    private $ordersCRUD;
    
    /** @var PHPMailer $mailer Instance de PHPMailer pour l'envoi d'emails */
    private $mailer;
    
    /** @var mysqli $mysqli Instance de connexion mysqli */
    private $mysqli;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->notificationsCRUD = new NotificationsCRUD($mysqli);
        $this->ordersCRUD = new OrdersCRUD($mysqli);
        $this->initializeMailer();
    }
    
    /**
     * Initialise la configuration du mailer avec les paramètres SMTP
     */
    private function initializeMailer() {
       
            require_once BASE_PATH . 'env_helper.php';
            
            $this->mailer = new PHPMailer(true);
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;

            // Récupération des identifiants
            //$email = get_env_variable('COMPANY_SUPPORT_EMAIL');
            //$password = get_env_variable('COMPANY_SUPPORT_EMAIL_CODE');
            $email = 'plutorede@gmail.com';
            $password = 'nrkt rurp dmwn jbou';
            
            // Vérification des identifiants
            if (empty($email) || $email === '20') {
                error_log("Erreur: Email de support non configuré correctement");
            }
            if (empty($password) || $password === '20') {
                error_log("Erreur: Code d'email de support non configuré correctement");
            }

            $this->mailer->Username = $email;
            $this->mailer->Password = $password;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;

            // Configuration supplémentaire
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';
            $this->mailer->isHTML(true);
      
    }
    
    /**
     * Récupère le template d'email en fonction du type
     * @param string $type - Type de template (admin_verification, password_reset, welcome, etc.)
     * @param array $data - Données à injecter dans le template
     * @return array|null - Retourne le template ou null si non trouvé
     */
    private function getEmailTemplate($data) {
        $type = $data['type']?? null;
        // Vérification et déclaration des variables nulles si elles ne sont pas définies
        $username = isset($_SESSION['user_username']) ? $_SESSION['user_username'] : null;
        $data['username'] = $data['username'] ?? $username;

        // Variables par défaut
        $data['code'] = $data['code'] ?? null;
        $data['order_id'] = $data['order_id'] ?? null;
        $data['message'] = $data['message'] ?? null;
        $data['items_html'] = $data['items_html'] ?? null;
        $data['total_ht'] = $data['total_ht'] ?? 25.6;
        $data['tva_amount'] = $data['tva_amount'] ?? 25.6;
        $data['total_ttc'] = $data['total_ttc'] ?? 25.6;
        $data['vat_rate'] = $data['vat_rate'] ?? 25.6;
        $data['base_url'] = $data['base_url'] ?? null;
        $shipping = [
            'shipping_street' => $shipping['shipping_street'] ?? null,
            'shipping_postal_code' => $shipping['shipping_postal_code'] ?? null,
            'shipping_city' => $shipping['shipping_city'] ?? null,
            'shipping_country' => $shipping['shipping_country'] ?? null
        ];

        require_once BASE_PATH . 'env_helper.php';

        if (isset($data['order_id'])) {
            // Récupération des détails de la commande
            $orderDetails = $this->ordersCRUD->get(
                [
                    'o.id', 'o.user_id', 'o.total_amount', 
                    'o.shipping_street', 'o.shipping_city', 
                    'o.shipping_postal_code', 'o.shipping_country',
                    'oi.quantity', 'oi.price', 
                    'p.name as product_name',
                    'u.username'
                ],
                ['o.id' => $data['order_id']],
                [
                    'joins' => [
                        [
                            'type' => 'LEFT',
                            'table' => 'order_items',
                            'table_alias' => 'oi',
                            'on' => 'o.id = oi.order_id'
                        ],
                        [
                            'type' => 'LEFT',
                            'table' => 'products',
                            'table_alias' => 'p',
                            'on' => 'oi.product_id = p.id'
                        ],
                        [
                            'type' => 'LEFT',
                            'table' => 'users',
                            'table_alias' => 'u',
                            'on' => 'o.user_id = u.id'
                        ]
                    ],
                    'table_alias' => 'o'
                ]
            );
            
            if ($orderDetails) {
                $shipping = $orderDetails[0];
                
                // Calcul des totaux avec TVA incluse
                $totalTTC = 0;
                $items_html = '';
                $vat_rate = (float)(get_env_variable('COMPANY_VAT_RATE') ?: '20');
                
                foreach ($orderDetails as $item) {
                    // Vérification des valeurs nulles
                    $quantity = $item['quantity'] ?? 0;
                    $price = $item['price'] ?? 0;
                    
                    $lineTTC = $price * $quantity;
                    $totalTTC += $lineTTC;
                    
                    // Calcul du prix HT (prix TTC / (1 + TVA))
                    $priceHT = $price / (1 + ($vat_rate/100));
                    $lineHT = $priceHT * $quantity;
                    
                    $items_html .= "<tr>
                        <td>{$item['product_name']}</td>
                        <td style='text-align:center'>{$quantity}</td>
                        <td style='text-align:right'>" . number_format($priceHT, 2, ',', ' ') . " €</td>
                        <td style='text-align:right'>" . number_format($lineHT, 2, ',', ' ') . " €</td>
                    </tr>";
                }
                
                // Calcul total HT et TVA (correction des formules)
                $totalHT = $totalTTC / (1 + ($vat_rate/100));
                $tva = $totalTTC - $totalHT;
                
                $data['items_html'] = $items_html;
                $data['total_ht'] = $totalHT;
                $data['tva_amount'] = $tva;
                $data['total_ttc'] = $totalTTC;
                $data['vat_rate'] = $vat_rate;
            }
        }

        $templates = [
            'admin_verification' => [
                'subject' => 'Code de vérification administrateur',
                'body' => "
                    <h2>Code de vérification administrateur</h2>
                    <p>Votre code de vérification est : <strong>{$data['code']}</strong></p>
                    <p>Ce code est valable pendant 15 minutes.</p>
                    <p>Si vous n'avez pas demandé ce code, veuillez ignorer cet email.</p>
                "
            ],
            'password_reset' => [
                'subject' => 'Réinitialisation de mot de passe',
                'body' => "
                    <h2>Réinitialisation de votre mot de passe</h2>
                    <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
                    <p>Votre code de réinitialisation est : <strong>{$data['code']}</strong></p>
                    <p>Ce code est valable pendant 1 heure.</p>
                "
            ],
            'welcome' => [
                'subject' => 'Bienvenue sur notre plateforme',
                'body' => "
                    <h2>Bienvenue {$data['username']} !</h2>
                    <p>Nous sommes ravis de vous compter parmi nos membres.</p>
                    <p>Votre compte a été créé avec succès.</p>
                "
            ],
            'order_confirmation' => [
                'subject' => 'Votre facture et confirmation de commande #'.$data['order_id'],
                'body' => "
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .invoice-container { max-width: 800px; margin: 0 auto; padding: 20px; }
                            .header { display: flex; margin-bottom: 30px; }
                            .company-info { margin-left: 20px; }
                            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                            th, td { padding: 10px; border: 1px solid #ddd; }
                            th { background: #f5f5f5; }
                            .totals { text-align: right; }
                        </style>
                    </head>
                    <body>
                        <div class='invoice-container'>
                            <div class='header'>
                                <img src='{$data['base_url']}/static/asset/logo.png' style='width: 80px;' alt='Logo'>
                                <div class='company-info'>
                                    <h1>" . get_env_variable('COMPANY_NAME') . "</h1>
                                    <p>" . get_env_variable('COMPANY_ADDRESS') . "</p>
                                    <p>" . get_env_variable('COMPANY_POSTAL_CODE') . " " . get_env_variable('COMPANY_CITY') . "</p>
                                </div>
                            </div>

                            <h2>Confirmation de commande n°{$data['order_id']}</h2>
                            
                            <p>Bonjour {$data['username']},</p>
                            <p>Nous vous remercions pour votre commande. Voici le récapitulatif :</p>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Quantité</th>
                                        <th>Prix unitaire HT</th>
                                        <th>Total HT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$data['items_html']}
                                </tbody>
                            </table>

                            <div class='totals'>
                                <p>Total HT : " . number_format($data['total_ht'], 2, ',', ' ') . " €</p>
                                <p>TVA ({$data['vat_rate']}%) : " . number_format($data['tva_amount'], 2, ',', ' ') . " €</p>
                                <p><strong>Total TTC : " . number_format($data['total_ttc'], 2, ',', ' ') . " €</strong></p>
                            </div>

                            <p>Votre commande sera expédiée à l'adresse suivante :</p>
                            <p>
                                {$shipping['shipping_street']}<br>
                                {$shipping['shipping_postal_code']} {$shipping['shipping_city']}<br>
                                {$shipping['shipping_country']}
                            </p>

                            
                            <p>En cas de question, de retour produit ou autre, n'hesiter pas a contacter à " . get_env_variable('COMPANY_SUPPORT_EMAIL') . "</p>
                            
                            <p>Merci de votre confiance !</p>
                        </div>
                    </body>
                    </html>
                "
            ],
            'ticket_support' => [
                'subject' => $data['subject'] ?? 'Support client',
                'body' => "
                    <html>
                    <head><meta charset='UTF-8'></head>
                    <body>
                        <h2>Demande de support client</h2>
                        <p>{$data['message']}</p>
                        " . (!empty($data['order_id']) ? "<p><strong>Commande :</strong> #{$data['order_id']}</p>" : "") . "
                    </body>
                    </html>
                "
            ],
            // Vous pouvez ajouter d'autres templates ici
        ];

        return $templates[$type] ?? null;
    }
    
    /**
     * Crée une nouvelle notification
     * 
     * @param array $data Données de la notification (user_id, type, message, etc.)
     * @return array Statut de l'opération
     */
    public function createNotification($data) {
        $userId = $data['user_id'] ?? null;
        $type = $data['type'] ?? null;
        $message = $data['message'] ?? null;
        $additionalData = $data['additional_data'] ?? null;
        
        if (!$userId || !$type || !$message) {
            return ['status' => 'error', 'message' => 'User ID, type and message are required'];
        }
        
        // Préparation des données pour l'insertion
        $notificationData = [
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($additionalData) {
            $notificationData['additional_data'] = json_encode($additionalData);
        }
        
        // Insertion de la notification
        $notificationId = $this->notificationsCRUD->insert($notificationData);
        
        if ($notificationId) {
            return [
                'status' => 'success', 
                'message' => 'Notification created successfully', 
                'data' => ['notification_id' => $notificationId]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to create notification'];
    }
    
    /**
     * Marque une notification comme lue
     * 
     * @param array $data Données de la notification (notification_id)
     * @return array Statut de l'opération
     */
    public function markAsRead($data) {
        $notificationId = $data['notification_id'] ?? null;
        
        if (!$notificationId) {
            return ['status' => 'error', 'message' => 'Notification ID is required'];
        }
        
        // Vérifier si la notification existe
        $notification = $this->notificationsCRUD->find($notificationId);
        
        if (!$notification) {
            return ['status' => 'error', 'message' => 'Notification not found'];
        }
        
        // Mise à jour de la notification
        $result = $this->notificationsCRUD->update(['is_read' => 1], ['id' => $notificationId]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'Notification marked as read'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to mark notification as read'];
    }
    
    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     * 
     * @param array $data Données de l'utilisateur (user_id)
     * @return array Statut de l'opération
     */
    public function markAllAsRead($data) {
        $userId = $data['user_id'] ?? null;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Mise à jour des notifications
        $result = $this->notificationsCRUD->update(['is_read' => 1], ['user_id' => $userId, 'is_read' => 0]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'All notifications marked as read'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to mark notifications as read'];
    }
    
    /**
     * Récupère les notifications d'un utilisateur
     * 
     * @param array $data Données de l'utilisateur (user_id, page, limit, unread_only)
     * @return array Liste des notifications
     */
    public function getUserNotifications($data) {
        $userId = $data['user_id'] ?? null;
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        $unreadOnly = isset($data['unread_only']) ? (bool)$data['unread_only'] : false;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Préparation des filtres
        $filters = ['user_id' => $userId];
        
        if ($unreadOnly) {
            $filters['is_read'] = 0;
        }
        
        // Récupération des notifications
        $notifications = $this->notificationsCRUD->get(
            ['*'],
            $filters,
            ['orderBy' => 'created_at', 'orderDirection' => 'DESC', 'limit' => $limit, 'offset' => $offset]
        );
        
        // Traitement des données additionnelles
        foreach ($notifications as &$notification) {
            if (isset($notification['additional_data'])) {
                $notification['additional_data'] = json_decode($notification['additional_data'], true);
            }
        }
        
        // Comptage du nombre total de notifications pour la pagination
        $total = $this->notificationsCRUD->count($filters);
        
        return [
            'status' => 'success', 
            'message' => 'Notifications retrieved successfully', 
            'data' => [
                'notifications' => $notifications,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ],
                'unread_count' => $this->notificationsCRUD->count(['user_id' => $userId, 'is_read' => 0])
            ]
        ];
    }
    
    /**
     * Supprime une notification
     * 
     * @param array $data Données de la notification (notification_id)
     * @return array Statut de l'opération
     */
    public function deleteNotification($data) {
        $notificationId = $data['notification_id'] ?? null;
        
        if (!$notificationId) {
            return ['status' => 'error', 'message' => 'Notification ID is required'];
        }
        
        // Vérifier si la notification existe
        $notification = $this->notificationsCRUD->find($notificationId);
        
        if (!$notification) {
            return ['status' => 'error', 'message' => 'Notification not found'];
        }
        
        // Suppression de la notification
        $result = $this->notificationsCRUD->delete(['id' => $notificationId]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'Notification deleted successfully'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to delete notification'];
    }
    
    /**
     * Envoie un email
     * 
     * @param array $data Données de l'email [email_data => [type, to], message, redirect]
     * @return array Statut de l'opération
     */
    public function sendEmail($data) {
        $message = $data['message'] ?? null;
        $redirect = $data['redirect'] ?? null;
        
        if (isset($data['email_data'])) {
            $emailData = $data['email_data'];
        } else {
            // Compatibilité avec l'ancien format
            $emailData = $data;
        }
        
        
        if (!isset($emailData['type']) || !isset($emailData['to'])) {
            return ['status' => 'error','message' => 'Email type and recipient are required'];
        }
        
        $template = $this->getEmailTemplate( $emailData);
        if (!$template) {
            return ['status' => 'error','message' => 'Email template not found'];
        }
    
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($emailData['to']);
        $this->mailer->Subject = $template['subject'];
        $this->mailer->Body = $template['body'];
        $success = $this->mailer->send();
        
        // Enregistrement de la notification
        if ($success && isset($emailData['user_id'])) {
            $this->createNotification([
                'user_id' => $emailData['user_id'],
                'type' => 'email',
                'message' => 'Email envoyé: ' . $template['subject'],
                'additional_data' => $emailData
            ]);
        }
        
        // Enregistrement de l'historique des emails si admin
        if (isset($_SESSION['admin']) && $_SESSION['admin'] != "pending") {
            $stmt = $this->mysqli->prepare("INSERT INTO notifications (user_id, type, status, created_at) VALUES (?, ?, ?, NOW())");
            $status = $success ? 'sent' : 'failed';
            $stmt->execute([$_SESSION['user_id'], $emailData['type'], $status]);
        }
        
        return [
            'status' => $success ? 'success' : 'error',
            'message' => ($success ? 'Email sent successfully' : 'Failed to send email') . ($message ? ' ' . $message : ''),
            'redirect' => $redirect,
        ];
    
       
    }
    
    /**
     * Envoie une notification push
     * 
     * @param array $data Données de la notification (user_id, title, body, data)
     * @return array Statut de l'opération
     */
    public function sendPushNotification($data) {
        $userId = $data['user_id'] ?? null;
        $title = $data['title'] ?? null;
        $body = $data['body'] ?? null;
        $additionalData = $data['data'] ?? null;
        
        if (!$userId || !$title || !$body) {
            return ['status' => 'error', 'message' => 'User ID, title and body are required'];
        }
        
        // Dans une vraie application, ici on enverrait la notification push
        // Pour l'exemple, on simule l'envoi en créant une notification en base
        
        $result = $this->createNotification([
            'user_id' => $userId,
            'type' => 'push',
            'message' => $title . ': ' . $body,
            'additional_data' => $additionalData
        ]);
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success', 
                'message' => 'Push notification sent successfully',
                'data' => ['notification_id' => $result['data']['notification_id']]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to send push notification'];
    }
}