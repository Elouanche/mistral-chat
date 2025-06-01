<?php
require_once CRUD_PATH . 'CartCRUD.php';
require_once CRUD_PATH . 'CartItemsCRUD.php';
require_once CRUD_PATH . 'ProductsCRUD.php';
require_once CRUD_PATH . 'ProductsImageCRUD.php';

/**
 * Service de gestion des paniers
 * Utilise CartCRUD et CartItemsCRUD pour les opérations de base de données
 * Gère également les paniers en session pour les utilisateurs non connectés
 */
class CartService {
    /** @var CartCRUD $cartCRUD Instance du CRUD panier */
    private $cartCRUD;
    
    /** @var CartItemsCRUD $cartItemsCRUD Instance du CRUD éléments de panier */
    private $cartItemsCRUD;
    
    /** @var ProductsCRUD $productsCRUD Instance du CRUD produits */
    private $productsCRUD;
    
    /** @var ProductsImageCRUD $productsImageCRUD Instance du CRUD images produits */
    private $productsImageCRUD;
    
    /** @var string $sessionKey Clé de session pour le panier */
    private $sessionKey = 'user_cart';
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->cartCRUD = new CartCRUD($mysqli);
        $this->cartItemsCRUD = new CartItemsCRUD($mysqli);
        $this->productsCRUD = new ProductsCRUD($mysqli);
        $this->productsImageCRUD = new ProductsImageCRUD($mysqli);
        
        // S'assurer que la session est démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialiser le panier en session si nécessaire
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [
                'items' => [],
                'total_amount' => 0,
                'item_count' => 0
            ];
        }
    }
    
    /**
     * Crée un nouveau panier pour un utilisateur connecté
     * 
     * @param array $data Données du panier (user_id)
     * @return array Statut de l'opération
     */
    public function createCart($data) {
        logInfo("Creating cart", ['user_id' => $data['user_id'] ?? null]);
        $userId = $data['user_id'] ?? null;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Vérifier si l'utilisateur a déjà un panier
        $existingCart = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
        
        if (!empty($existingCart)) {
            return ['status' => 'success', 'message' => 'Cart already exists', 'data' => ['cart_id' => $existingCart[0]['id']]];
        }
        
        // Création du panier
        $cartId = $this->cartCRUD->insert(['user_id' => $userId]);
        
        if ($cartId) {
            return [
                'status' => 'success', 
                'message' => 'Cart created successfully', 
                'data' => ['cart_id' => $cartId]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to create cart'];
    }
    
    /**
     * Ajoute un produit au panier
     * 
     * @param array $data Données de l'élément (cart_id/user_id, product_id, quantity)
     * @return array Statut de l'opération
     */
    public function addToCart($data) {
        logInfo("Adding item to cart", [
            'user_id' => $data['user_id'] ?? null,
            'product_id' => $data['product_id'] ?? null
        ]);
        $cartId = $data['cart_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $productId = $data['product_id'] ?? null;
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
        
        if ($quantity <= 0) {
            return ['status' => 'error', 'message' => 'Quantity must be greater than 0'];
        }
        
        if (!$productId) {
            return ['status' => 'error', 'message' => 'Product ID is required'];
        }
        
        // Vérifier si le produit existe
        $product = $this->productsCRUD->find($productId);
        
        if (!$product) {
            return ['status' => 'error', 'message' => 'Product not found'];
        }
        
        // Si l'utilisateur est connecté (user_id présent)
        if ($userId) {
            // Récupérer le panier de l'utilisateur ou en créer un nouveau
            $carts = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
            
            if (empty($carts)) {
                // Créer un nouveau panier pour l'utilisateur
                $cartId = $this->cartCRUD->insert(['user_id' => $userId]);
                
                if (!$cartId) {
                    return ['status' => 'error', 'message' => 'Failed to create cart'];
                }
            } else {
                $cartId = $carts[0]['id'];
            }
            
            // Vérifier si le produit est déjà dans le panier
            $cartItems = $this->cartItemsCRUD->get(['id', 'quantity'], [
                'cart_id' => $cartId,
                'product_id' => $productId
            ]);
            
            if (!empty($cartItems)) {
                // Mettre à jour la quantité
                $newQuantity = $cartItems[0]['quantity'] + $quantity;
                $result = $this->cartItemsCRUD->update(['quantity' => $newQuantity], ['id' => $cartItems[0]['id']]);
                
                if ($result) {
                    return [
                        'status' => 'success', 
                        'message' => 'Cart item quantity updated successfully', 
                        'data' => ['cart_item_id' => $cartItems[0]['id'], 'quantity' => $newQuantity]
                    ];
                }
                
                return ['status' => 'error', 'message' => 'Failed to update cart item quantity'];
            }
            
            // Ajouter le produit au panier
            $cartItemId = $this->cartItemsCRUD->insert([
                'cart_id' => $cartId,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
            
            if ($cartItemId) {
                return [
                    'status' => 'success', 
                    'message' => 'Product added to cart successfully', 
                    'data' => ['cart_item_id' => $cartItemId]
                ];
            }
            
            return ['status' => 'error', 'message' => 'Failed to add product to cart'];
        } 
        // Utilisateur non connecté - utiliser le panier en session
        else {
            $sessionCart = &$_SESSION[$this->sessionKey];
            
            // Générer un ID temporaire pour l'élément
            $tempItemId = 'temp_' . uniqid();
            
            // Vérifier si le produit est déjà dans le panier
            $existingItemKey = null;
            foreach ($sessionCart['items'] as $key => $item) {
                if ($item['product_id'] == $productId) {
                    $existingItemKey = $key;
                    break;
                }
            }
            
            if ($existingItemKey !== null) {
                // Mettre à jour la quantité
                $newQuantity = $sessionCart['items'][$existingItemKey]['quantity'] + $quantity;
                $sessionCart['items'][$existingItemKey]['quantity'] = $newQuantity;
                $itemTotal = $product['price'] * $newQuantity;
                $sessionCart['items'][$existingItemKey]['item_total'] = $itemTotal;
                
                // Recalculer le total du panier
                $this->recalculateSessionCart();
                
                return [
                    'status' => 'success', 
                    'message' => 'Cart item quantity updated successfully', 
                    'data' => [
                        'cart_item_id' => $sessionCart['items'][$existingItemKey]['cart_item_id'],
                        'quantity' => $newQuantity
                    ]
                ];
            }
            
            // Ajouter le produit au panier en session
            $itemTotal = $product['price'] * $quantity;
            $sessionCart['items'][] = [
                'cart_item_id' => $tempItemId,
                'product_id' => $productId,
                'product_name' => $product['name'],
                'product_description' => $product['description'] ?? '',
                'price' => $product['price'],
                'quantity' => $quantity,
                'item_total' => $itemTotal
            ];
            
            // Recalculer le total du panier
            $this->recalculateSessionCart();
            
            return [
                'status' => 'success', 
                'message' => 'Product added to cart successfully', 
                'data' => ['cart_item_id' => $tempItemId]
            ];
        }
    }
    
    /**
     * Met à jour la quantité d'un élément du panier
     * 
     * @param array $data Données de l'élément (cart_item_id/product_id, quantity)
     * @return array Statut de l'opération
     */
    public function updateCartItem($data) {
        logInfo("Updating cart item", [
            'cart_item_id' => $data['cart_item_id'] ?? null,
            'quantity' => $data['quantity'] ?? null
        ]);
        $cartItemId = $data['cart_item_id'] ?? null;
        $productId = $data['product_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : null;
        
        if (!$cartItemId && !$productId) {
            return ['status' => 'error', 'message' => 'Cart item ID or Product ID is required'];
        }
        
        if ($quantity === null) {
            return ['status' => 'error', 'message' => 'Quantity is required'];
        }
        
        if ($quantity <= 0) {
            // Si la quantité est 0 ou négative, supprimer l'élément du panier
            if ($productId) {
                return $this->removeFromCart(['product_id' => $productId, 'user_id' => $userId]);
            } else {
                return $this->removeFromCart(['cart_item_id' => $cartItemId]);
            }
        }
        
        // Pour les utilisateurs connectés
        if ($userId) {
            // Trouver le panier de l'utilisateur
            $carts = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
            
            if (empty($carts)) {
                return ['status' => 'error', 'message' => 'Cart not found'];
            }
            
            $cartId = $carts[0]['id'];
            
            // Si on a un product_id plutôt qu'un cart_item_id
            if ($productId && !$cartItemId) {
                // Rechercher l'item dans le panier
                $items = $this->cartItemsCRUD->get(['id'], [
                    'cart_id' => $cartId,
                    'product_id' => $productId
                ]);
                
                if (empty($items)) {
                    return ['status' => 'error', 'message' => 'Cart item not found'];
                }
                
                $cartItemId = $items[0]['id'];
            }
            
            // Mettre à jour la quantité
            $result = $this->cartItemsCRUD->update(['quantity' => $quantity], ['id' => $cartItemId]);
            
            if ($result) {
                return [
                    'status' => 'success', 
                    'message' => 'Cart item quantity updated successfully', 
                    'data' => ['cart_item_id' => $cartItemId, 'quantity' => $quantity]
                ];
            }
            
            return ['status' => 'error', 'message' => 'Failed to update cart item quantity'];
        }
        
        // Pour les utilisateurs non connectés
        $sessionCart = &$_SESSION[$this->sessionKey];
        
        // Si on a un cart_item_id temporaire
        if ($cartItemId && strpos($cartItemId, 'temp_') === 0) {
            foreach ($sessionCart['items'] as &$item) {
                if ($item['cart_item_id'] === $cartItemId) {
                    $item['quantity'] = $quantity;
                    $item['item_total'] = $item['price'] * $quantity;
                    $this->recalculateSessionCart();
                    
                    return [
                        'status' => 'success',
                        'message' => 'Cart item quantity updated successfully',
                        'data' => $item
                    ];
                }
            }
        }
        // Si on a un product_id
        elseif ($productId) {
            foreach ($sessionCart['items'] as &$item) {
                if ($item['product_id'] === $productId) {
                    $item['quantity'] = $quantity;
                    $item['item_total'] = $item['price'] * $quantity;
                    $this->recalculateSessionCart();
                    
                    return [
                        'status' => 'success',
                        'message' => 'Cart item quantity updated successfully',
                        'data' => $item
                    ];
                }
            }
        }
        
        return ['status' => 'error', 'message' => 'Cart item not found in session'];
    }
    
    /**
     * Supprime un élément du panier
     * 
     * @param array $data Données de l'élément (cart_item_id)
     * @return array Statut de l'opération
     */
    public function removeFromCart($data) {
        logInfo("Removing item from cart", [
            'user_id' => $data['user_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'cart_item_id' => $data['cart_item_id'] ?? null
        ]);

        $userId = $data['user_id'] ?? null;
        $productId = $data['product_id'] ?? null;
        $cartItemId = $data['cart_item_id'] ?? null;

        // Pour les utilisateurs connectés
        if ($userId && $productId) {
            // Trouver le panier de l'utilisateur
            $carts = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
            if (empty($carts)) {
                return ['status' => 'error', 'message' => 'Cart not found'];
            }
            
            $cartId = $carts[0]['id'];
            
            // Trouver l'item dans le panier
            $cartItems = $this->cartItemsCRUD->get(['id'], [
                'cart_id' => $cartId,
                'product_id' => $productId
            ]);

            if (empty($cartItems)) {
                return ['status' => 'error', 'message' => 'Cart item not found'];
            }

            $cartItemId = $cartItems[0]['id'];
        }

        // Si on a un cart_item_id (soit direct, soit trouvé via product_id)
        if ($cartItemId) {
            // Vérifier si c'est un élément de session
            if (strpos($cartItemId, 'temp_') === 0) {
                $sessionCart = &$_SESSION[$this->sessionKey];
                
                // Trouver l'élément dans le panier en session
                $itemKey = null;
                foreach ($sessionCart['items'] as $key => $item) {
                    if ($item['cart_item_id'] == $cartItemId) {
                        $itemKey = $key;
                        break;
                    }
                }
                
                if ($itemKey === null) {
                    return ['status' => 'error', 'message' => 'Cart item not found'];
                }
                
                // Supprimer l'élément
                array_splice($sessionCart['items'], $itemKey, 1);
                
                // Recalculer le total du panier
                $this->recalculateSessionCart();
                
                return [
                    'status' => 'success', 
                    'message' => 'Cart item removed successfully',
                    'data' => $sessionCart['items']
                ];
            } 
            // Sinon, c'est un élément en base de données
            else {
                // Supprimer l'élément
                $result = $this->cartItemsCRUD->delete(['id' => $cartItemId]);
                
                if ($result) {
                    // Récupérer le panier mis à jour
                    $updatedCart = $this->getCart(['user_id' => $userId]);
                    return [
                        'status' => 'success',
                        'message' => 'Cart item removed successfully',
                        'data' => $updatedCart['data']
                    ];
                }
                
                return ['status' => 'error', 'message' => 'Failed to remove cart item'];
            }
        }

        return ['status' => 'error', 'message' => 'Invalid cart item identification'];
    }
    
    /**
     * Vide le panier
     * 
     * @param array $data Données du panier (cart_id/user_id)
     * @return array Statut de l'opération
     */
    public function clearCart($data) {
        logInfo("Clearing cart", [
            'cart_id' => $data['cart_id'] ?? null,
            'user_id' => $data['user_id'] ?? null
        ]);
        $cartId = $data['cart_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        
        // Si user_id ou cart_id est présent, c'est un panier en base de données
        if ($cartId || $userId) {
            if (!$cartId && $userId) {
                $carts = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
                
                if (empty($carts)) {
                    return ['status' => 'error', 'message' => 'Cart not found'];
                }
                
                $cartId = $carts[0]['id'];
            }
            
            // Supprimer tous les éléments du panier
            $result = $this->cartItemsCRUD->delete(['cart_id' => $cartId]);
            
            if ($result) {
                return ['status' => 'success', 'message' => 'Cart cleared successfully'];
            }
            
            return ['status' => 'error', 'message' => 'Failed to clear cart'];
        } 
        // Sinon, c'est un panier en session
        else {
            // Réinitialiser le panier en session
            $_SESSION[$this->sessionKey] = [
                'items' => [],
                'total_amount' => 0,
                'item_count' => 0
            ];
            
            return ['status' => 'success', 'message' => 'Cart cleared successfully'];
        }
    }
    
    /**
     * Récupère le contenu du panier
     * 
     * @param array $data Données du panier (cart_id/user_id)
     * @return array Contenu du panier
     */
    public function getCart($data) {
        logInfo("Getting cart", [
            'cart_id' => $data['cart_id'] ?? null,
            'user_id' => $data['user_id'] ?? null
        ]);
        $cartId = $data['cart_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        
        // Si user_id ou cart_id est présent, c'est un panier en base de données
        if ($cartId || $userId) {
            if (!$cartId && $userId) {
                $carts = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
                
                if (empty($carts)) {
                    // Créer un nouveau panier pour l'utilisateur
                    $cartId = $this->cartCRUD->insert(['user_id' => $userId]);
                    
                    if (!$cartId) {
                        return ['status' => 'error', 'message' => 'Failed to create cart'];
                    }
                } else {
                    $cartId = $carts[0]['id'];
                }
            }
            
            // Récupérer les éléments du panier
            $cartItems = $this->cartItemsCRUD->get(['*'], ['cart_id' => $cartId]);
            
            // Récupérer les informations des produits pour chaque élément
            $cartItemsWithProducts = [];
            $totalAmount = 0;
            
            foreach ($cartItems as $item) {
                $product = $this->productsCRUD->find($item['product_id']);
                
                if ($product) {
                    // Récupérer l'image principale du produit
                    $images = $this->productsImageCRUD->get(['image_path'], [
                        'product_id' => $product['id'],
                        'is_main' => 1
                    ]);
                    
                    $mainImage = !empty($images) ? $images[0]['image_path'] : '';
                    
                    // S'assurer que le prix est un nombre à virgule flottante
                    $price = floatval($product['price']);
                    $quantity = intval($item['quantity']);
                    $itemTotal = round($price * $quantity, 2);
                    $totalAmount += $itemTotal;
                    
                    $cartItemsWithProducts[] = [
                        'cart_item_id' => $item['id'],
                        'product_id' => $product['id'],
                        'product_name' => $product['name'],
                        'product_description' => $product['description'] ?? '',
                        'product_image' => $mainImage,
                        'price' => $price,
                        'quantity' => $quantity,
                        'item_total' => $itemTotal
                    ];
                }
            }
            
            // Arrondir le total final à 2 décimales
            $totalAmount = round($totalAmount, 2);
                
            return [
                'status' => 'success', 
                'message' => 'Cart retrieved successfully', 
                'data' => [
                    'cart_id' => $cartId,
                    'items' => $cartItemsWithProducts,
                    'total_amount' => $totalAmount,
                    'item_count' => count($cartItemsWithProducts)
                ]
            ];
        } 
        // Sinon, retourner le panier en session
        else {
            $sessionCart = $_SESSION[$this->sessionKey];
            
            return [
                'status' => 'success', 
                'message' => 'Cart retrieved successfully', 
                'data' => [
                    'cart_id' => 'session',
                    'items' => $sessionCart['items'],
                    'total_amount' => $sessionCart['total_amount'],
                    'item_count' => $sessionCart['item_count']
                ]
            ];
        }
    }
    /**
     * Fusionne le panier en session avec le panier d'un utilisateur connecté
     * 
     * @param array $data Données de fusion (user_id)
     * @return array Statut de l'opération
     */
    public function mergeCart($data) {
        logInfo("Merging cart", ['user_id' => $data['user_id'] ?? null]);
        $userId = $data['user_id'] ?? null;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        $sessionCart = $_SESSION[$this->sessionKey];
        
        if (empty($sessionCart['items'])) {
            return ['status' => 'success', 'message' => 'No items to merge'];
        }
        
        // Récupérer ou créer le panier de l'utilisateur
        $carts = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
        
        if (empty($carts)) {
            $cartId = $this->cartCRUD->insert(['user_id' => $userId]);
            
            if (!$cartId) {
                return ['status' => 'error', 'message' => 'Failed to create cart'];
            }
        } else {
            $cartId = $carts[0]['id'];
        }
        
        // Transférer les éléments du panier en session vers le panier de l'utilisateur
        foreach ($sessionCart['items'] as $item) {
            // Vérifier si le produit existe déjà dans le panier de l'utilisateur
            $existingItems = $this->cartItemsCRUD->get(['id', 'quantity'], [
                'cart_id' => $cartId,
                'product_id' => $item['product_id']
            ]);
            
            if (!empty($existingItems)) {
                // Mettre à jour la quantité
                $newQuantity = $existingItems[0]['quantity'] + $item['quantity'];
                $this->cartItemsCRUD->update(['quantity' => $newQuantity], ['id' => $existingItems[0]['id']]);
            } else {
                // Ajouter le produit au panier
                $this->cartItemsCRUD->insert([
                    'cart_id' => $cartId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ]);
            }
        }
        
        // Vider le panier en session
        $_SESSION[$this->sessionKey] = [
            'items' => [],
            'total_amount' => 0,
            'item_count' => 0
        ];
        
        return [
            'status' => 'success', 
            'message' => 'Cart merged successfully',
            'data' => ['cart_id' => $cartId]
        ];
    }
    /**
     * Convertit un panier en commande
     * 
     * @param array $data Données de conversion (cart_id/user_id, shipping_info)
     * @return array Statut de l'opération
     */
    public function checkoutCart($data) {
        logInfo("Checking out cart", [
            'cart_id' => $data['cart_id'] ?? null,
            'user_id' => $data['user_id'] ?? null
        ]);
        $cartId = $data['cart_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $shippingInfo = $data['shipping_info'] ?? [];
        
        // Vérification des informations d'expédition
        if (empty($shippingInfo) || 
            !isset($shippingInfo['street']) || 
            !isset($shippingInfo['city']) || 
            !isset($shippingInfo['state']) || 
            !isset($shippingInfo['postal_code']) || 
            !isset($shippingInfo['country'])) {
            return ['status' => 'error', 'message' => 'Complete shipping information is required'];
        }
        
        // Si user_id ou cart_id est présent, c'est un panier en base de données
        if ($cartId || $userId) {
            if (!$cartId && $userId) {
                $carts = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
                
                if (empty($carts)) {
                    return ['status' => 'error', 'message' => 'Cart not found'];
                }
                
                $cartId = $carts[0]['id'];
            }
            
            // Récupérer les éléments du panier
            $cartItems = $this->cartItemsCRUD->get(['*'], ['cart_id' => $cartId]);
            
            if (empty($cartItems)) {
                return ['status' => 'error', 'message' => 'Cart is empty'];
            }
            
            // Préparer les éléments pour la commande
            $orderItems = [];
            $totalAmount = 0;
            
            foreach ($cartItems as $item) {
                $product = $this->productsCRUD->find($item['product_id']);
                
                if ($product) {
                    $itemTotal = $product['price'] * $item['quantity'];
                    $totalAmount += $itemTotal;
                    
                    $orderItems[] = [
                        'product_id' => $product['id'],
                        'quantity' => $item['quantity'],
                        'price' => $product['price']
                    ];
                }
            }
            
            // Transférer au service de commande
            return [
                'status' => 'pending',
                'service' => 'Order',
                'action' => 'createOrder',
                'data' => [
                    'message' => 'Cart checkout initiated',
                    'user_id' => $userId,
                    'items' => $orderItems,
                    'shipping_info' => $shippingInfo,
                    'total_amount' => $totalAmount,
                    'cart_id' => $cartId
                ]
            ];
        } 
        // Sinon, c'est un panier en session
        else {
            $sessionCart = $_SESSION[$this->sessionKey];
            
            if (empty($sessionCart['items'])) {
                return ['status' => 'error', 'message' => 'Cart is empty'];
            }
            
            // Préparer les éléments pour la commande
            $orderItems = [];
            
            foreach ($sessionCart['items'] as $item) {
                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
            }
            
            // Transférer au service de commande
            $result = [
                'status' => 'pending',
                'service' => 'Order',
                'action' => 'createOrder',
                'data' => [
                    'message' => 'Cart checkout initiated',
                    'user_id' => $userId,  // Peut être null pour un utilisateur non connecté
                    'is_guest' => true,    // Indicateur d'achat invité
                    'items' => $orderItems,
                    'shipping_info' => $shippingInfo,
                    'total_amount' => $sessionCart['total_amount']
                ]
            ];
            
            // Vider le panier en session après la commande
            $_SESSION[$this->sessionKey] = [
                'items' => [],
                'total_amount' => 0,
                'item_count' => 0
            ];
            
            return $result;
        }
    }
    
    /**
     * Recalcule les totaux du panier en session
     */
    private function recalculateSessionCart() {
        $sessionCart = &$_SESSION[$this->sessionKey];
        
        $totalAmount = 0;
        foreach ($sessionCart['items'] as $item) {
            $totalAmount += $item['item_total'];
        }
        
        $sessionCart['total_amount'] = $totalAmount;
        $sessionCart['item_count'] = count($sessionCart['items']);
    }
        
    /**
     * Augmente la quantité d'un produit dans le panier
     * 
     * @param array $data Données de l'élément (product_id, user_id)
     * @return array Statut de l'opération
     */
    public function increaseQuantity($data) {
        logInfo("Increasing product quantity in cart", [
            'product_id' => $data['product_id'] ?? null,
            'user_id' => $data['user_id'] ?? null
        ]);
        $productId = $data['product_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        
        if (!$productId) {
            return ['status' => 'error', 'message' => 'Product ID is required'];
        }
        
        // Vérifier si le produit existe
        $product = $this->productsCRUD->find($productId);
        if (!$product) {
            return ['status' => 'error', 'message' => 'Product not found'];
        }
        
        // Pour les utilisateurs connectés
        if ($userId) {
            // Trouver ou créer le panier
            $carts = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
            $cartId = null;
            
            if (empty($carts)) {
                $cartId = $this->cartCRUD->insert(['user_id' => $userId]);
                if (!$cartId) {
                    return ['status' => 'error', 'message' => 'Failed to create cart'];
                }
            } else {
                $cartId = $carts[0]['id'];
            }
            
            // Vérifier si le produit est déjà dans le panier
            $cartItems = $this->cartItemsCRUD->get(['id', 'quantity'], [
                'cart_id' => $cartId,
                'product_id' => $productId
            ]);
            
            if (empty($cartItems)) {
                // Ajouter le produit au panier
                $cartItemId = $this->cartItemsCRUD->insert([
                    'cart_id' => $cartId,
                    'product_id' => $productId,
                    'quantity' => 1
                ]);
                
                if (!$cartItemId) {
                    return ['status' => 'error', 'message' => 'Failed to add product to cart'];
                }
                
                // Récupérer les données complètes du produit pour la réponse
                return [
                    'status' => 'success',
                    'message' => 'Product added to cart successfully',
                    'data' => [
                        'cart_item_id' => $cartItemId,
                        'product_id' => $productId,
                        'product_name' => $product['name'],
                        'product_description' => $product['description'] ?? '',
                        'price' => $product['price'],
                        'quantity' => 1,
                        'item_total' => $product['price']
                    ]
                ];
            }
            
            // Mettre à jour la quantité
            $newQuantity = $cartItems[0]['quantity'] + 1;
            $success = $this->cartItemsCRUD->update(
                ['quantity' => $newQuantity],
                ['id' => $cartItems[0]['id']]
            );
            
            if (!$success) {
                return ['status' => 'error', 'message' => 'Failed to update cart item quantity'];
            }
            
            // Récupérer les données mises à jour
            return [
                'status' => 'success',
                'message' => 'Cart item quantity updated successfully',
                'data' => [
                    'cart_item_id' => $cartItems[0]['id'],
                    'product_id' => $productId,
                    'product_name' => $product['name'],
                    'product_description' => $product['description'] ?? '',
                    'price' => $product['price'],
                    'quantity' => $newQuantity,
                    'item_total' => $product['price'] * $newQuantity
                ]
            ];
        }
        
        // Pour les utilisateurs non connectés
        $sessionCart = &$_SESSION[$this->sessionKey];
        
        // Vérifier si le produit est déjà dans le panier
        $existingItemKey = null;
        foreach ($sessionCart['items'] as $key => $item) {
            if ($item['product_id'] == $productId) {
                $existingItemKey = $key;
                break;
            }
        }
        
        if ($existingItemKey !== null) {
            // Mettre à jour la quantité
            $newQuantity = $sessionCart['items'][$existingItemKey]['quantity'] + 1;
            $sessionCart['items'][$existingItemKey]['quantity'] = $newQuantity;
            $itemTotal = $product['price'] * $newQuantity;
            $sessionCart['items'][$existingItemKey]['item_total'] = $itemTotal;
            
            // Recalculer le total du panier
            $this->recalculateSessionCart();
            
            return [
                'status' => 'success',
                'message' => 'Cart item quantity updated successfully',
                'data' => $sessionCart['items']
            ];
        } else {
            // Générer un ID temporaire pour l'élément
            $tempItemId = 'temp_' . uniqid();
            
            // Ajouter le produit au panier en session
            $itemTotal = $product['price'] * 1;
            $sessionCart['items'][] = [
                'cart_item_id' => $tempItemId,
                'product_id' => $productId,
                'product_name' => $product['name'],
                'product_description' => $product['description'] ?? '',
                'price' => $product['price'],
                'quantity' => 1,
                'item_total' => $itemTotal
            ];
            
            // Recalculer le total du panier
            $this->recalculateSessionCart();
            
            return [
                'status' => 'success',
                'message' => 'Product added to cart successfully',
                'data' => $sessionCart['items']
            ];
        }
    }
    
    /**
     * Diminue la quantité d'un produit dans le panier
     * 
     * @param array $data Données de l'élément (product_id, user_id)
     * @return array Statut de l'opération
     */
    public function decreaseQuantity($data) {
        logInfo("Decreasing item quantity", [
            'product_id' => $data['product_id'] ?? null,
            'user_id' => $data['user_id'] ?? null
        ]);
    
        $productId = $data['product_id'] ?? null;
        $userId = $data['user_id'] ?? null;
    
        if (!$productId) {
            return ['status' => 'error', 'message' => 'Product ID is required'];
        }
    
        // Récupérer le produit
        $product = $this->productsCRUD->find($productId);
        if (!$product) {
            return ['status' => 'error', 'message' => 'Product not found'];
        }
    
        // Utilisateur connecté
        if ($userId) {
            $carts = $this->cartCRUD->get(['id'], ['user_id' => $userId]);
    
            if (empty($carts)) {
                return ['status' => 'error', 'message' => 'Cart not found'];
            }
    
            $cartId = $carts[0]['id'];
    
            $cartItems = $this->cartItemsCRUD->get(['id', 'quantity'], [
                'cart_id' => $cartId,
                'product_id' => $productId
            ]);
    
            if (empty($cartItems)) {
                return ['status' => 'error', 'message' => 'Product not in cart'];
            }
    
            $cartItemId = $cartItems[0]['id'];
            $currentQuantity = $cartItems[0]['quantity'];
    
            if ($currentQuantity <= 1) {
                return $this->removeFromCart(['cart_item_id' => $cartItemId]);
            }
    
            $newQuantity = $currentQuantity - 1;
            $result = $this->cartItemsCRUD->update(['quantity' => $newQuantity], ['id' => $cartItemId]);
    
            if ($result) {
                return [
                    'status' => 'success',
                    'message' => 'Cart item quantity updated successfully',
                    'data' => [
                        'cart_item_id' => $cartItemId,
                        'product_id' => $productId,
                        'product_name' => $product['name'],
                        'product_description' => $product['description'] ?? '',
                        'price' => $product['price'],
                        'quantity' => $newQuantity,
                        'item_total' => $product['price'] * $newQuantity
                    ]
                ];
            }
    
            return ['status' => 'error', 'message' => 'Failed to update cart item quantity'];
        }
    
        // Utilisateur non connecté
        $sessionCart = &$_SESSION[$this->sessionKey];
    
        $existingItemKey = null;
        foreach ($sessionCart['items'] as $key => $item) {
            if ($item['product_id'] == $productId) {
                $existingItemKey = $key;
                break;
            }
        }
    
        if ($existingItemKey === null) {
            return ['status' => 'error', 'message' => 'Product not in cart'];
        }
    
        $currentQuantity = $sessionCart['items'][$existingItemKey]['quantity'];
    
        if ($currentQuantity <= 1) {
            array_splice($sessionCart['items'], $existingItemKey, 1);
            $this->recalculateSessionCart();
    
            return [
                'status' => 'success',
                'message' => 'Cart item removed successfully',
                'data' => $sessionCart['items']
            ];
        }
    
        $newQuantity = $currentQuantity - 1;
        $price = $sessionCart['items'][$existingItemKey]['price'];
        $sessionCart['items'][$existingItemKey]['quantity'] = $newQuantity;
        $sessionCart['items'][$existingItemKey]['item_total'] = $price * $newQuantity;
    
        $this->recalculateSessionCart();
    
        return [
            'status' => 'success',
            'message' => 'Cart item quantity updated successfully',
            'data' => $sessionCart['items']
        ];
    }
    
    
    
    
    
    
   
}