<?php
/**
 * Index des fonctions CRUD
 * Ce fichier référence toutes les fonctions disponibles dans les classes CRUD
 * et fournit des exemples d'utilisation pour chacune d'entre elles.
 */

// Inclure tous les fichiers CRUD
require_once __DIR__ . '/BaseCRUD.php';
require_once __DIR__ . '/UsersCRUD.php';
require_once __DIR__ . '/ProductsCRUD.php';
require_once __DIR__ . '/ProductsImageCRUD.php';
require_once __DIR__ . '/ReviewsCRUD.php';
require_once __DIR__ . '/ReviewResponsesCRUD.php';
require_once __DIR__ . '/OrdersCRUD.php';
require_once __DIR__ . '/OrderItemsCRUD.php';
require_once __DIR__ . '/CartCRUD.php';
require_once __DIR__ . '/CartItemsCRUD.php';
require_once __DIR__ . '/PaymentsCRUD.php';
require_once __DIR__ . '/DeliveriesCRUD.php';
require_once __DIR__ . '/ReturnedsCRUD.php';
require_once __DIR__ . '/AnalyticsCRUD.php';
require_once __DIR__ . '/NotificationsCRUD.php';
require_once __DIR__ . '/MonitoringCRUD.php';
require_once __DIR__ . '/ErrorLogsCRUD.php';

/**
 * ====================================
 * DOCUMENTATION DES CLASSES CRUD
 * ====================================
 */

/**
 * BaseCRUD - Classe de base pour toutes les opérations CRUD
 * 
 * Méthodes disponibles:
 * ---------------------
 * - __construct($mysqli, $table) : Initialise une instance CRUD avec la connexion et la table spécifiées
 *   Exemple: $crud = new BaseCRUD($mysqli, 'ma_table');
 * 
 * - getAll() : Récupère tous les enregistrements de la table
 *   Exemple: $allRecords = $crud->getAll();
 * 
 * - getById($id) : Récupère un enregistrement par son ID
 *   Exemple: $record = $crud->getById(5);
 * 
 * - create($data) : Crée un nouvel enregistrement
 *   Exemple: $newId = $crud->create(['nom' => 'Valeur', 'champ2' => 'Valeur2']);
 * 
 * - update($id, $data) : Met à jour un enregistrement existant
 *   Exemple: $success = $crud->update(5, ['nom' => 'Nouvelle valeur']);
 * 
 * - delete($id) : Supprime un enregistrement
 *   Exemple: $success = $crud->delete(5);
 * 
 * - findBy($criteria) : Recherche des enregistrements selon des critères
 *   Exemple: $records = $crud->findBy(['categorie' => 'A', 'actif' => 1]);
 * 
 * - check($field, $compareValue, $conditions) : Vérifie ou compare des informations
 *   Exemple: $exists = $crud->check('id', null, ['email' => 'user@example.com']);
 *   Exemple: $isValid = $crud->check('password', 'motdepasse123', ['id' => 1]);
 * 
 * - get($searchParams, $returnFields) : Récupère des enregistrements avec critères et champs spécifiques
 *   Exemple: $data = $crud->get(['categorie' => 'A'], ['id', 'nom', 'prix']);
 */

/**
 * UsersCRUD - Opérations CRUD pour la table users
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getUserByEmail($email) : Récupère un utilisateur par son email
 * - authenticate($email, $password) : Authentifie un utilisateur
 * - updatePassword($userId, $newPassword) : Met à jour le mot de passe d'un utilisateur
 * - activateUser($userId) : Active un compte utilisateur
 * - deactivateUser($userId) : Désactive un compte utilisateur
 */

/**
 * ProductsCRUD - Opérations CRUD pour la table products
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getProductsByCategory($categoryId) : Récupère les produits d'une catégorie
 * - searchProducts($keyword) : Recherche des produits par mot-clé
 * - updateStock($productId, $quantity) : Met à jour le stock d'un produit
 * - getFeaturedProducts() : Récupère les produits mis en avant
 * - getProductsOnSale() : Récupère les produits en promotion
 */

/**
 * ProductsImageCRUD - Opérations CRUD pour la table products_image
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getImagesByProductId($productId) : Récupère toutes les images d'un produit
 * - getMainImage($productId) : Récupère l'image principale d'un produit
 * - addImage($productId, $imagePath, $isMain) : Ajoute une image à un produit
 * - setAsMain($imageId) : Définit une image comme image principale
 * - deleteAllProductImages($productId) : Supprime toutes les images d'un produit
 * - countProductImages($productId) : Compte le nombre d'images pour un produit
 */

/**
 * ReviewsCRUD - Opérations CRUD pour la table reviews
 * 
 * Structure de la table:
 * ---------------------
 * - id INT AUTO_INCREMENT PRIMARY KEY
 * - user_id INT NOT NULL
 * - product_id INT NOT NULL
 * - rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5)
 * - comment TEXT
 * - created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * - updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * - FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
 * - FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getReviewsByProductId($productId) : Récupère tous les avis d'un produit
 *   Exemple: $productReviews = $reviewsCRUD->getReviewsByProductId(5);
 * 
 * - getReviewsByUserId($userId) : Récupère tous les avis d'un utilisateur
 *   Exemple: $userReviews = $reviewsCRUD->getReviewsByUserId(3);
 * 
 * - createReview($userId, $productId, $rating, $comment) : Crée ou met à jour un avis
 *   Exemple: $reviewId = $reviewsCRUD->createReview(1, 5, 4, 'Très bon produit');
 * 
 * - calculateAverageRating($productId) : Calcule la note moyenne d'un produit
 *   Exemple: $averageRating = $reviewsCRUD->calculateAverageRating(5);
 * 
 * - countReviews($productId) : Compte le nombre d'avis pour un produit
 *   Exemple: $reviewCount = $reviewsCRUD->countReviews(5);
 * 
 * - getRatingDistribution($productId) : Récupère la distribution des notes
 *   Exemple: $distribution = $reviewsCRUD->getRatingDistribution(5);
 * 
 * - hasUserReviewed($userId, $productId) : Vérifie si un utilisateur a déjà laissé un avis
 *   Exemple: $hasReviewed = $reviewsCRUD->hasUserReviewed(1, 5);
 * 
 * - getUserReview($userId, $productId) : Récupère l'avis d'un utilisateur pour un produit
 *   Exemple: $review = $reviewsCRUD->getUserReview(1, 5);
 */

/**
 * ReviewResponsesCRUD - Opérations CRUD pour la table review_responses
 * 
 * Structure de la table:
 * ---------------------
 * - id INT AUTO_INCREMENT PRIMARY KEY
 * - review_id INT NOT NULL
 * - admin_id INT NOT NULL
 * - response TEXT NOT NULL
 * - created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * - updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * - FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
 * - FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getResponsesByReviewId($reviewId) : Récupère toutes les réponses pour un avis
 *   Exemple: $responses = $responsesCRUD->getResponsesByReviewId(10);
 * 
 * - createResponse($reviewId, $adminId, $response) : Crée une nouvelle réponse
 *   Exemple: $responseId = $responsesCRUD->createResponse(10, 1, "Merci pour votre avis");
 * 
 * - hasResponse($reviewId) : Vérifie si un avis a déjà une réponse
 *   Exemple: if ($responsesCRUD->hasResponse(10)) { echo "Cet avis a déjà une réponse"; }
 * 
 * - updateResponse($responseId, $response) : Met à jour une réponse
 *   Exemple: $success = $responsesCRUD->updateResponse(5, "Nouvelle réponse");
 * 
 * - deleteResponse($responseId) : Supprime une réponse
 *   Exemple: $success = $responsesCRUD->deleteResponse(5);
 * 
 * - getResponsesByAdminId($adminId) : Récupère toutes les réponses d'un administrateur
 *   Exemple: $adminResponses = $responsesCRUD->getResponsesByAdminId(1);
 */

/**
 * OrdersCRUD - Opérations CRUD pour la table orders
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getOrdersByUserId($userId) : Récupère les commandes d'un utilisateur
 * - getOrderDetails($orderId) : Récupère les détails d'une commande
 * - updateOrderStatus($orderId, $status) : Met à jour le statut d'une commande
 * - getRecentOrders($limit) : Récupère les commandes récentes
 */

/**
 * OrderItemsCRUD - Opérations CRUD pour la table order_items
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getItemsByOrderId($orderId) : Récupère les articles d'une commande
 * - addItem($orderId, $productId, $quantity, $price) : Ajoute un article à une commande
 * - updateItemQuantity($itemId, $quantity) : Met à jour la quantité d'un article
 */

/**
 * CartCRUD - Opérations CRUD pour la table cart
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getCartByUserId($userId) : Récupère le panier d'un utilisateur
 * - createCart($userId) : Crée un nouveau panier pour un utilisateur
 * - clearCart($cartId) : Vide un panier
 * - getCartTotal($cartId) : Calcule le total d'un panier
 */

/**
 * CartItemsCRUD - Opérations CRUD pour la table cart_items
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getItemsByCartId($cartId) : Récupère les articles d'un panier
 * - addItem($cartId, $productId, $quantity) : Ajoute un article à un panier
 * - updateItemQuantity($itemId, $quantity) : Met à jour la quantité d'un article
 * - removeItem($itemId) : Supprime un article du panier
 */

/**
 * PaymentsCRUD - Opérations CRUD pour la table payments
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getPaymentsByOrderId($orderId) : Récupère les paiements d'une commande
 * - createPayment($orderId, $amount, $method, $status) : Crée un nouveau paiement
 * - updatePaymentStatus($paymentId, $status) : Met à jour le statut d'un paiement
 */

/**
 * DeliveriesCRUD - Opérations CRUD pour la table deliveries
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getDeliveryByOrderId($orderId) : Récupère la livraison d'une commande
 * - createDelivery($orderId, $address, $status) : Crée une nouvelle livraison
 * - updateDeliveryStatus($deliveryId, $status) : Met à jour le statut d'une livraison
 * - updateTrackingNumber($deliveryId, $trackingNumber) : Met à jour le numéro de suivi
 */

/**
 * ReturnedsCRUD - Opérations CRUD pour la table returneds
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getReturnsByOrderId($orderId) : Récupère les retours d'une commande
 * - createReturn($orderId, $reason, $status) : Crée un nouveau retour
 * - updateReturnStatus($returnId, $status) : Met à jour le statut d'un retour
 */

/**
 * AnalyticsCRUD - Opérations CRUD pour la table analytics
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - recordPageView($page, $userId = null) : Enregistre une vue de page
 * - getPageViews($page, $startDate, $endDate) : Récupère les vues d'une page
 * - getMostViewedPages($limit) : Récupère les pages les plus vues
 */

/**
 * NotificationsCRUD - Opérations CRUD pour la table notifications
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - getNotificationsByUserId($userId) : Récupère les notifications d'un utilisateur
 * - createNotification($userId, $type, $message) : Crée une nouvelle notification
 * - markAsRead($notificationId) : Marque une notification comme lue
 * - getUnreadCount($userId) : Compte les notifications non lues
 */

/**
 * MonitoringCRUD - Opérations CRUD pour la table monitoring
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - recordEvent($type, $details) : Enregistre un événement système
 * - getEvents($startDate, $endDate, $type = null) : Récupère les événements
 * - getRecentEvents($limit) : Récupère les événements récents
 */

/**
 * ErrorLogsCRUD - Opérations CRUD pour la table error_logs
 * 
 * Méthodes spécifiques:
 * ---------------------
 * - logError($type, $message, $details = null) : Enregistre une erreur
 * - getErrors($startDate, $endDate, $type = null) : Récupère les erreurs
 * - getRecentErrors($limit) : Récupère les erreurs récentes
 */

// Afficher un message indiquant que ce fichier est destiné à la documentation
echo "<h1>Documentation des classes CRUD</h1>";
echo "<p>Ce fichier est destiné à servir de référence pour l'utilisation des classes CRUD.</p>";
echo "<p>Pour utiliser ces classes, incluez le fichier CRUD correspondant dans votre code.</p>";
echo "<p>Exemple: <code>require_once 'CRUD/ReviewsCRUD.php';</code></p>";