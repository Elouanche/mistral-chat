-- Migrations pour le système de chat IA
USE sc1feir2687_loremipsum;

-- Table pour les modèles d'IA disponibles
CREATE TABLE IF NOT EXISTS `ai_models` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `model_name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nom technique du modèle utilisé par l\'API',
    `display_name` VARCHAR(100) NOT NULL COMMENT 'Nom d\'affichage pour l\'interface utilisateur',
    `description` TEXT COMMENT 'Description des capacités du modèle',
    `parameters` JSON COMMENT 'Paramètres par défaut pour ce modèle (température, tokens, etc.)',
    `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Indique si le modèle est disponible pour les utilisateurs',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- Table pour les conversations avec l'IA
CREATE TABLE IF NOT EXISTS `ai_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `model_id` INT NOT NULL,
    `title` VARCHAR(191) NOT NULL COMMENT 'Titre de la conversation',
    `system_prompt` TEXT COMMENT 'Prompt système pour définir le comportement de l\'IA',
    `is_archived` BOOLEAN DEFAULT FALSE COMMENT 'Indique si la conversation est archivée',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`model_id`) REFERENCES `ai_models`(`id`)
) ENGINE = InnoDB;

-- Table pour les messages dans les conversations
CREATE TABLE IF NOT EXISTS `ai_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `role` ENUM('user', 'assistant', 'system') NOT NULL COMMENT 'Rôle de l\'émetteur du message',
    `content` TEXT NOT NULL COMMENT 'Contenu du message',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Table pour les requêtes envoyées à l'API
CREATE TABLE IF NOT EXISTS `ai_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `conversation_id` INT NULL COMMENT 'Peut être NULL pour les requêtes hors conversation',
    `model_id` INT NOT NULL,
    `prompt` TEXT NOT NULL COMMENT 'Prompt envoyé à l\'API',
    `parameters` JSON COMMENT 'Paramètres utilisés pour cette requête',
    `status` ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `error_message` TEXT COMMENT 'Message d\'erreur en cas d\'échec',
    `request_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `response_timestamp` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`model_id`) REFERENCES `ai_models`(`id`)
) ENGINE = InnoDB;

-- Table pour les réponses de l'API
CREATE TABLE IF NOT EXISTS `ai_responses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT NOT NULL,
    `response_text` TEXT NOT NULL COMMENT 'Réponse de l\'API',
    `completion_tokens` INT COMMENT 'Nombre de tokens utilisés pour la réponse',
    `prompt_tokens` INT COMMENT 'Nombre de tokens utilisés pour la requête',
    `total_tokens` INT COMMENT 'Nombre total de tokens utilisés',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`request_id`) REFERENCES `ai_requests`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Table pour suivre l'utilisation de l'API par utilisateur
CREATE TABLE IF NOT EXISTS `api_usage` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `model_id` INT NOT NULL,
    `tokens_used` INT NOT NULL COMMENT 'Nombre de tokens utilisés',
    `request_count` INT NOT NULL DEFAULT 1 COMMENT 'Nombre de requêtes',
    `usage_date` DATE NOT NULL COMMENT 'Date d\'utilisation',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`model_id`) REFERENCES `ai_models`(`id`),
    UNIQUE KEY `user_model_date` (`user_id`, `model_id`, `usage_date`)
) ENGINE = InnoDB;

-- Table pour les abonnements
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `plan_id` INT NOT NULL,
    `status` ENUM('active', 'cancelled', 'expired') NOT NULL DEFAULT 'active',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`)
) ENGINE = InnoDB;

-- Table pour les plans d'abonnement
CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10, 2) NOT NULL,
    `duration_days` INT NOT NULL COMMENT 'Durée en jours',
    `token_limit` INT NOT NULL COMMENT 'Limite de tokens par mois',
    `features` JSON COMMENT 'Fonctionnalités incluses dans le plan',
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- Insertion des modèles Mistral par défaut
INSERT INTO `ai_models` (`model_name`, `display_name`, `description`, `parameters`, `is_active`) VALUES
('mistral-tiny', 'Mistral Tiny', 'Modèle léger et rapide pour des tâches simples', '{"temperature": 0.7, "max_tokens": 800}', TRUE),
('mistral-small', 'Mistral Small', 'Bon équilibre entre performance et rapidité', '{"temperature": 0.7, "max_tokens": 1000}', TRUE),
('mistral-medium', 'Mistral Medium', 'Modèle avancé pour des tâches complexes', '{"temperature": 0.7, "max_tokens": 1500}', TRUE),
('mistral-large', 'Mistral Large', 'Notre modèle le plus puissant pour des résultats optimaux', '{"temperature": 0.7, "max_tokens": 2000}', TRUE);

-- Insertion des plans d'abonnement par défaut
INSERT INTO `subscription_plans` (`name`, `description`, `price`, `duration_days`, `token_limit`, `features`, `is_active`) VALUES
('Gratuit', 'Accès limité aux fonctionnalités de base', 0.00, 30, 10000, '{"models": ["mistral-tiny"], "priority_support": false}', TRUE),
('Standard', 'Idéal pour un usage personnel régulier', 9.99, 30, 100000, '{"models": ["mistral-tiny", "mistral-small"], "priority_support": false}', TRUE),
('Pro', 'Pour les professionnels exigeants', 19.99, 30, 300000, '{"models": ["mistral-tiny", "mistral-small", "mistral-medium"], "priority_support": true}', TRUE),
('Entreprise', 'Solution complète pour les entreprises', 49.99, 30, 1000000, '{"models": ["mistral-tiny", "mistral-small", "mistral-medium", "mistral-large"], "priority_support": true}', TRUE);