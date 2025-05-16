-- Vérifier si la base de données existe, si non, la créer
CREATE DATABASE IF NOT EXISTS sc1feir2687_loremipsum;
USE sc1feir2687_loremipsum;

-- Table pour les utilisateurs
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(191) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(191) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- Table pour stocker les produits
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- Table pour stocker le panier d'un utilisateur
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Table pour les articles du panier
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE = InnoDB;

-- Table pour gérer les commandes
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE = InnoDB;

-- Table pour les articles des commandes
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE = InnoDB;

-- Table pour les retours
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS returneds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Table pour les conversations de support
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS support_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(191),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE = InnoDB;

-- Table pour les messages de support
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES support_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id)
) ENGINE = InnoDB;

-- Table pour gérer les avis des utilisateurs
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE = InnoDB;

-- Table pour les réponses aux avis
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS review_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    admin_id INT NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE = InnoDB;

-- Table pour les transactions de paiement
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE = InnoDB;

-- Table pour les notifications
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,  -- Pour catégoriser le type de notification (email, système, etc.)
    status VARCHAR(20) NOT NULL DEFAULT 'unread',  -- Pour suivre l'état (lu/non lu)
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Table pour gérer le suivi des colis
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    tracking_number VARCHAR(191),
    delivery_service VARCHAR(191),
    status VARCHAR(50) DEFAULT 'In Transit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE = InnoDB;

-- Table pour les logs d'erreurs
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(191) NOT NULL,
    error_message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- Table pour surveiller les services
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(191) NOT NULL,
    status VARCHAR(50) DEFAULT 'Healthy',
    last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- Table pour les statistiques analytiques
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE IF NOT EXISTS analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(191) NOT NULL,
    value DECIMAL(10, 2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `products_image`;
CREATE TABLE IF NOT EXISTS `products_image` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

USE sc1feir2687_loremipsum;

-- Ajout d'utilisateurs
INSERT INTO users (username, email, password_hash, is_admin) VALUES
('admin', 'admin@example.com', 'hashed_password1', 1),  -- TRUE devient 1
('user1', 'user1@example.com', 'hashed_password2', 0),  -- FALSE devient 0
('user2', 'user2@example.com', 'hashed_password3', 0),
('test', 'test@example.com', '$2y$10$PXqF1z2pljuo5WDhMFb/QeBbMjrcKgpEPIK6bIcYEZ1qATj/Hp8wK', 0);

-- Ajout de produits
INSERT INTO products (name, description, price, stock) VALUES
('Laptop', 'A high performance laptop', 999.99, 50),
('Smartphone', 'Latest model smartphone', 699.99, 200),
('Headphones', 'Noise cancelling headphones', 199.99, 150),
('Camera', 'Professional DSLR camera', 1199.99, 30),
('Tourne-visse', 'La prescision avant tout', 24.99, 300);


-- Ajout de paniers
INSERT INTO cart (user_id) VALUES
(1), -- Panier pour admin
(2); -- Panier pour user1

-- Ajout d'articles aux paniers
INSERT INTO cart_items (cart_id, product_id, quantity) VALUES
(1, 1, 1), -- 1 Laptop dans le panier de admin
(2, 2, 2); -- 2 Smartphones dans le panier de user1

-- Ajout de commandes
INSERT INTO orders (user_id, status, total_amount, shipping_address) VALUES
(1, 'Pending', 999.99, '123 Admin Street, City, Country'),
(2, 'Completed', 1399.98, '456 User1 Avenue, City, Country');

-- Ajout d'articles aux commandes
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 999.99), -- 1 Laptop dans la commande de user1
(1, 2, 2, 699.99), -- 2 Smartphones dans la commande de user1
(2, 3, 1, 199.99); -- 1 Headphones dans la commande de user2

-- Ajout de retours
INSERT INTO returneds (user_id, order_id, status, reason) VALUES
(1, 1, 'Pending', 'Product not as described'),
(2, 2, 'Completed', 'Changed mind');

-- Ajout de conversations de support
INSERT INTO support_conversations (user_id, subject) VALUES
(1, 'Order issue'),
(2, 'Product inquiry');

-- Ajout de messages de support
INSERT INTO support_messages (conversation_id, sender_id, message) VALUES
(1, 1, 'I need help with my order.'),
(2, 2, 'Could you provide more details about the product?');

-- Ajout d'avis
INSERT INTO reviews (user_id, product_id, rating, comment) VALUES
(1, 1, 5, 'Excellent laptop! Highly recommended.'),
(2, 2, 4, 'Good smartphone, but a bit pricey.');

-- Ajout de réponses aux avis
INSERT INTO review_responses (review_id, admin_id, response) VALUES
(1, 1, 'Thank you for your feedback!'),
(2, 1, 'We appreciate your thoughts!');

-- Ajout de paiements
INSERT INTO payments (order_id, user_id, payment_method, amount, status) VALUES
(1, 1, 'Credit Card', 999.99, 'Pending'),
(2, 2, 'PayPal', 1399.98, 'Successful');

-- Ajout de notifications
INSERT INTO notifications (user_id, message, type, status) VALUES
(1, 'Your order is on the way!', 'order_status', 'unread'),
(2, 'Your product inquiry has been answered.', 'inquiry_response', 'unread');

-- Ajout de suivis de livraison
INSERT INTO deliveries (order_id, tracking_number, delivery_service, status) VALUES
(1, 'TRACK123', 'UPS', 'In Transit'),
(2, 'TRACK456', 'FedEx', 'Delivered');

-- Ajout de logs d'erreurs
INSERT INTO error_logs (service_name, error_message) VALUES
('PaymentService', 'Transaction timeout'),
('DeliveryService', 'Tracking number not found');

-- Surveillance des services
INSERT INTO monitoring (service_name, status) VALUES
('AuthService', 'Healthy'),
('InventoryService', 'Degraded');

-- Ajout de statistiques analytiques
INSERT INTO analytics (metric_name, value) VALUES
('TotalSales', 2399.97),
('ActiveUsers', 2);


--
-- Déchargement des données de la table `products_image`
--

INSERT INTO `products_image` (`id`, `product_id`, `image_path`, `is_main`) VALUES
(1, 5, 'tourne-visse.webp', 1);