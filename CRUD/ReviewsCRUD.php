<?php
require_once __DIR__ . '/BaseCRUD.php';

/*
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE = InnoDB;
 */
class ReviewsCRUD extends BaseCRUD {
    
    /**
     * Constructeur
     * 
     * Exemple d'utilisation:
     * $reviewsCRUD = new ReviewsCRUD();
     */
    public function __construct($mysqli = null) {
        if ($mysqli === null) {
            require_once CONFIG_PATH . 'coDB.php';
            $mysqli = coDB();
        }
        parent::__construct($mysqli, 'reviews');
    }
    
  
}