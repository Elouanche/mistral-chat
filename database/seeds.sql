


USE sc1feir2687_mistral_chat;
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

-- ==========================================
-- Données pour les tables de l'API Mistral
-- ==========================================

-- Ajout des modèles Mistral disponibles
INSERT INTO mistral_models (model_name, description, parameters, is_active) VALUES
('mistral-tiny', 'Modèle léger pour des réponses rapides', '{"temperature": 0.7, "max_tokens": 1024, "top_p": 1}', 1),
('mistral-small', 'Équilibre entre performance et précision', '{"temperature": 0.7, "max_tokens": 2048, "top_p": 1}', 1),
('mistral-medium', 'Modèle avancé pour des réponses détaillées', '{"temperature": 0.7, "max_tokens": 4096, "top_p": 1}', 1),
('mistral-large', 'Modèle le plus performant pour des tâches complexes', '{"temperature": 0.7, "max_tokens": 8192, "top_p": 1}', 1);

-- Ajout des clés API pour les utilisateurs
INSERT INTO api_keys (user_id, api_key, provider, is_active) VALUES
(1, 'sk-test-api-key-123456789', 'mistral', 1),
(3, 'sk-admin-api-key-987654321', 'mistral', 1),
(4, 'sk-jessy-api-key-123789456', 'mistral', 1);

-- Ajout de conversations Mistral
INSERT INTO mistral_conversations (user_id, title, description, model_id, system_prompt) VALUES
(1, 'Aide sur les produits', 'Questions sur les caractéristiques des produits', 2, 'Tu es un assistant commercial expert en technologies. Aide le client à trouver le produit qui lui convient.'),
(3, 'Rédaction de descriptions', 'Génération de descriptions de produits', 3, 'Tu es un rédacteur marketing spécialisé dans la rédaction de descriptions de produits tech attrayantes.'),
(4, 'Support technique', 'Conversation sur des problèmes techniques', 2, 'Tu es un expert technique qui aide à résoudre des problèmes informatiques.');

-- Ajout de messages dans les conversations
INSERT INTO mistral_messages (conversation_id, role, content, tokens_used) VALUES
(1, 'system', 'Tu es un assistant commercial expert en technologies. Aide le client à trouver le produit qui lui convient.', 25),
(1, 'user', 'Quelle est la différence entre le laptop et le smartphone dans votre catalogue ?', 20),
(1, 'assistant', 'Le laptop offre une puissance de calcul supérieure avec un écran plus grand, idéal pour le travail et les tâches intensives. Le smartphone est portable, compact et parfait pour une utilisation mobile. Le laptop coûte 999,99€ tandis que le smartphone est à 699,99€. Que recherchez-vous principalement dans votre appareil ?', 60),
(2, 'system', 'Tu es un rédacteur marketing spécialisé dans la rédaction de descriptions de produits tech attrayantes.', 22),
(2, 'user', 'Peux-tu me rédiger une description plus attrayante pour notre tournevis ?', 18),
(2, 'assistant', 'Découvrez notre tournevis de précision professionnel - l\'outil indispensable pour tous vos travaux minutieux. Sa poignée ergonomique offre une prise en main parfaite, tandis que sa pointe en acier trempé garantit durabilité et précision à chaque utilisation. Que vous soyez bricoleur du dimanche ou professionnel exigeant, sa polyvalence vous séduira pour tous vos projets, de l\'électronique fine à l\'assemblage de meubles. La précision n\'a jamais été aussi accessible !', 85);

-- Ajout de requêtes API
INSERT INTO mistral_requests (user_id, conversation_id, model_id, prompt, parameters, status, tokens_prompt, tokens_completion, latency_ms, request_id) VALUES
(1, 1, 2, 'Quelle est la différence entre le laptop et le smartphone dans votre catalogue ?', '{"temperature": 0.7, "max_tokens": 2048}', 'completed', 20, 60, 543, 'req_123456789'),
(3, 2, 3, 'Peux-tu me rédiger une description plus attrayante pour notre tournevis ?', '{"temperature": 0.8, "max_tokens": 4096}', 'completed', 18, 85, 732, 'req_987654321'),
(4, 3, 2, 'Comment réinitialiser un smartphone qui ne démarre plus ?', '{"temperature": 0.7, "max_tokens": 2048}', 'pending', 15, 0, NULL, 'req_456123789');

-- Ajout de réponses API
INSERT INTO mistral_responses (request_id, response_text, finish_reason, raw_response) VALUES
(1, 'Le laptop offre une puissance de calcul supérieure avec un écran plus grand, idéal pour le travail et les tâches intensives. Le smartphone est portable, compact et parfait pour une utilisation mobile. Le laptop coûte 999,99€ tandis que le smartphone est à 699,99€. Que recherchez-vous principalement dans votre appareil ?', 'stop', '{"id":"resp_123456789","object":"chat.completion","created":1715856000,"model":"mistral-small","choices":[{"index":0,"message":{"role":"assistant","content":"Le laptop offre une puissance de calcul supérieure avec un écran plus grand, idéal pour le travail et les tâches intensives. Le smartphone est portable, compact et parfait pour une utilisation mobile. Le laptop coûte 999,99€ tandis que le smartphone est à 699,99€. Que recherchez-vous principalement dans votre appareil ?"},"finish_reason":"stop"}],"usage":{"prompt_tokens":20,"completion_tokens":60,"total_tokens":80}}'),
(2, 'Découvrez notre tournevis de précision professionnel - l\'outil indispensable pour tous vos travaux minutieux. Sa poignée ergonomique offre une prise en main parfaite, tandis que sa pointe en acier trempé garantit durabilité et précision à chaque utilisation. Que vous soyez bricoleur du dimanche ou professionnel exigeant, sa polyvalence vous séduira pour tous vos projets, de l\'électronique fine à l\'assemblage de meubles. La précision n\'a jamais été aussi accessible !', 'stop', '{"id":"resp_987654321","object":"chat.completion","created":1715856100,"model":"mistral-medium","choices":[{"index":0,"message":{"role":"assistant","content":"Découvrez notre tournevis de précision professionnel - l\'outil indispensable pour tous vos travaux minutieux. Sa poignée ergonomique offre une prise en main parfaite, tandis que sa pointe en acier trempé garantit durabilité et précision à chaque utilisation. Que vous soyez bricoleur du dimanche ou professionnel exigeant, sa polyvalence vous séduira pour tous vos projets, de l\'électronique fine à l\'assemblage de meubles. La précision n\'a jamais été aussi accessible !"},"finish_reason":"stop"}],"usage":{"prompt_tokens":18,"completion_tokens":85,"total_tokens":103}}');

-- Ajout de données d'utilisation
INSERT INTO api_usage (user_id, model_id, request_id, input_tokens, output_tokens, estimated_cost, usage_date) VALUES
(1, 2, 1, 20, 60, 0.000450, CURRENT_DATE),
(3, 3, 2, 18, 85, 0.000823, CURRENT_DATE);

-- Ajout de quotas pour les utilisateurs
INSERT INTO api_quotas (user_id, max_tokens_per_day, max_requests_per_minute, max_monthly_cost) VALUES
(1, 50000, 20, 5.00),
(3, 500000, 60, 50.00),
(4, 50000, 20, 5.00);

-- Ajout de logs API
INSERT INTO api_logs (user_id, request_id, event_type, details) VALUES
(1, 1, 'request_completed', '{"duration_ms": 543, "status": "success"}'),
(3, 2, 'request_completed', '{"duration_ms": 732, "status": "success"}'),
(4, 3, 'request_initiated', '{"timestamp": "2025-05-16T14:25:30Z"}');