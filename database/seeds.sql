USE sc1feir2687_lorempsum;

-- Ajout d'utilisateurs
INSERT INTO users (id, username, email, password_hash, is_admin) VALUES
(1, 'test', 'test@example.com', '$2y$10$PXqF1z2pljuo5WDhMFb/QeBbMjrcKgpEPIK6bIcYEZ1qATj/Hp8wK', 0),
(2, 'user2', 'user2@example.com', 'hashed_password3', 0),
(3, 'admin', 'jessypiquerel6@gmail.com', '$2y$10$PXqF1z2pljuo5WDhMFb/QeBbMjrcKgpEPIK6bIcYEZ1qATj/Hp8wK', 1),
(4, 'jessy', 'jessypiquerel3@gmail.com', '$2y$10$FAX/NdOVWcjUl8Q5ffvkneEMDhwOl4yS9vEuNfLM8tOOS2jJpzb/O', 0);

-- Ajout de produits
INSERT INTO products (name, description, price, stock) VALUES
('Laptop', 'A high performance laptop', 999.99, 50),
('Smartphone', 'Latest model smartphone', 699.99, 200),
('Headphones', 'Noise cancelling headphones', 199.99, 150),
('Camera', 'Professional DSLR camera', 1199.99, 30),
('Tourne-visse', 'La précision avant tout', 24.99, 300);

-- Ajout d'image de produit
INSERT INTO products_image (product_id, image_path, is_main) VALUES 
(5, 'tourne-visse.webp', 1);

-- Ajout de paniers
INSERT INTO cart (user_id) VALUES
(1),
(2),
(3);

-- Ajout d'articles aux paniers
INSERT INTO cart_items (cart_id, product_id, quantity) VALUES
(1, 5, 3),
(3, 5, 3);

-- Ajout de commandes
INSERT INTO orders (
    user_id, status, total_amount, shipping_street, shipping_city, 
    shipping_state, shipping_postal_code, shipping_country
) VALUES
(1, 'Pending', 999.99, '1 Rue de Paris', 'Paris', 'Île-de-France', '75001', 'FR'),
(2, 'Completed', 1399.98, '10 Avenue des Sciences', 'Paris', 'Île-de-France', '75005', 'FR');

-- Ajout d'articles aux commandes
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 999.99),
(1, 2, 2, 699.99),
(2, 3, 1, 199.99);

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
('ActiveUsers', 2.00);