<?php
require_once CRUD_PATH . '/ProductsCRUD.php';

/**
 * Service de gestion des produits
 * Utilise ProductsCRUD pour les opérations de base de données
 */
class ProductService {
    /** @var ProductsCRUD $productsCRUD Instance du CRUD produits */
    private $productsCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->productsCRUD = new ProductsCRUD($mysqli);
    }
    
    /**
     * Crée un nouveau produit
     * 
     * @param array $data Données du produit (name, description, price, stock)
     * @return array Statut de l'opération
     */
    public function createProduct($data) {
        logInfo("Creating new product", ['name' => $data['name'] ?? $data['product_name'] ?? null]);

        $name = $data['name'] ?? $data['product_name'] ?? null;
        $description = $data['description'] ?? $data['product_description'] ?? null;
        $price = $data['price'] ?? $data['product_price'] ?? null;
        $stock = $data['stock'] ?? $data['product_stock'] ?? 0;
        
        if (!$name || !$price) {
            return ['status' => 'error', 'message' => 'Product name and price are required'];
        }
        
        // Préparation des données pour l'insertion
        $productData = [
            'name' => $name,
            'price' => $price,
            'stock' => $stock
        ];
        
        if ($description) {
            $productData['description'] = $description;
        }
        
        // Insertion du produit
        $productId = $this->productsCRUD->insert($productData);
        
        if ($productId) {
            return [
                'status' => 'success', 
                'message' => 'Product created successfully', 
                'data' => ['product_id' => $productId]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to create product'];
    }
    
    /**
     * Met à jour un produit existant
     * 
     * @param array $data Données du produit (product_id, name, description, price, stock)
     * @return array Statut de l'opération
     */
    public function updateProduct($data) {
        logInfo("Updating product", ['product_id' => $data['product_id'] ?? null]);
        
        $productId = $data['product_id'] ?? null;
        
        if (!$productId) {
            return ['status' => 'error', 'message' => 'Product ID is required'];
        }
        
        // Vérifier si le produit existe
        $product = $this->productsCRUD->find($productId);
        if (!$product) {
            return ['status' => 'error', 'message' => 'Product not found'];
        }
        
        $updates = [];
        
        // Mise à jour du nom si fourni
        if (isset($data['name']) || isset($data['product_name'])) {
            $updates['name'] = $data['name'] ?? $data['product_name'];
        }
        
        // Mise à jour de la description si fournie
        if (isset($data['description']) || isset($data['product_description'])) {
            $updates['description'] = $data['description'] ?? $data['product_description'];
        }
        
        // Mise à jour du prix si fourni
        if (isset($data['price']) || isset($data['product_price'])) {
            $updates['price'] = $data['price'] ?? $data['product_price'];
        }
        
        // Mise à jour du stock si fourni
        if (isset($data['stock']) || isset($data['product_stock'])) {
            $updates['stock'] = $data['stock'] ?? $data['product_stock'];
        }
        
        if (empty($updates)) {
            return ['status' => 'error', 'message' => 'No fields to update'];
        }
        
        // Mise à jour du produit
        $result = $this->productsCRUD->update($updates, ['id' => $productId]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'Product updated successfully'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to update product'];
    }
    
    /**
     * Supprime un produit
     * 
     * @param array $data Données du produit (product_id)
     * @return array Statut de l'opération
     */
    public function deleteProduct($data) {
        logInfo("Deleting product", ['product_id' => $data['product_id'] ?? null]);
        
        $productId = $data['product_id'] ?? null;
        
        if (!$productId) {
            return ['status' => 'error', 'message' => 'Product ID is required'];
        }
        
        // Vérifier si le produit existe
        $product = $this->productsCRUD->find($productId);
        if (!$product) {
            return ['status' => 'error', 'message' => 'Product not found'];
        }
        
        // Suppression du produit
        $result = $this->productsCRUD->delete(['id' => $productId]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'Product deleted successfully'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to delete product'];
    }
    
    /**
     * Récupère les informations d'un produit
     * 
     * @param array $data Données du produit (product_id)
     * @return array Informations du produit
     */
    public function getProduct($data) {
        logInfo("Getting product details", ['product_id' => $data['product_id'] ?? null]);
        
        $productId = $data['product_id'] ?? null;
        
        if (!$productId) {
            return ['status' => 'error', 'message' => 'Product ID is required'];
        }
        
        // Récupération du produit
        $product = $this->productsCRUD->find($productId);
        
        if ($product) {
            return ['status' => 'success', 'message' => 'Product retrieved successfully', 'data' => $product];
        }
        
        return ['status' => 'error', 'message' => 'Product not found'];
    }
    
    /**
     * Liste les produits avec pagination et filtres optionnels
     * 
     * @param array $data Données de pagination et filtres (page, limit, filters)
     * @return array Liste des produits
     */
    public function listProducts($data) {
        logInfo("Listing products", [
            'page' => $data['page'] ?? 1,
            'limit' => $data['limit'] ?? 10
        ]);
        
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        $filters = $data['filters'] ?? [];
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Options pour la requête
        $options = ['limit' => $limit, 'offset' => $offset];
        
        // Ajout du tri si spécifié
        if (isset($data['sort_by'])) {
            $options['orderBy'] = $data['sort_by'];
            $options['orderDirection'] = $data['sort_direction'] ?? 'ASC';
        }
        
        // Récupération des produits
        $products = $this->productsCRUD->get(['*'], $filters, $options);
        
        // Comptage du nombre total de produits pour la pagination
        $total = $this->productsCRUD->count($filters);
        
        return [
            'status' => 'success', 
            'message' => 'Products retrieved successfully', 
            'data' => [
                'products' => $products,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ];
    }
    
    /**
     * Met à jour le stock d'un produit
     * 
     * @param array $data Données du produit (product_id, quantity)
     * @return array Statut de l'opération
     */
    public function updateStock($data) {
        logInfo("Updating product stock", [
            'id' => $data['product_id'] ?? null,
            'quantity' => $data['quantity'] ?? null
        ]);
        
        $productId = $data['product_id'] ?? null;
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : null;
        
        if (!$productId || $quantity === null) {
            return ['status' => 'error', 'message' => 'Product ID and quantity are required'];
        }
        
        // Vérifier si le produit existe
        $product = $this->productsCRUD->find($productId);
        if (!$product) {
            return ['status' => 'error', 'message' => 'Product not found'];
        }
        
        // Mise à jour du stock
        $result = $this->productsCRUD->update(['stock' => $quantity], ['id' => $productId]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'Product stock updated successfully'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to update product stock'];
    }
    
    /**
     * Vérifie si un produit est en stock
     * 
     * @param array $data Données du produit (product_id, quantity)
     * @return array Statut de la vérification
     */
    public function checkStock($data) {
        $productId = $data['product_id'] ?? null;
        $requestedQuantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
        
        if (!$productId) {
            return ['status' => 'error', 'message' => 'Product ID is required'];
        }
        
        // Récupération du produit
        $product = $this->productsCRUD->find($productId, ['id', 'name', 'stock']);
        
        if (!$product) {
            return ['status' => 'error', 'message' => 'Product not found'];
        }
        
        $available = $product['stock'] >= $requestedQuantity;
        
        return [
            'status' => 'success',
            'message' => $available ? 'Product is in stock' : 'Product is out of stock',
            'data' => [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'available' => $available,
                'requested_quantity' => $requestedQuantity,
                'available_quantity' => $product['stock']
            ]
        ];
    }
}