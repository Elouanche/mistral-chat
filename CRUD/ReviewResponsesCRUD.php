<?php
require_once __DIR__ . '/BaseCRUD.php';

/*
CREATE TABLE IF NOT EXISTS review_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    admin_id INT NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB;
 */
class ReviewResponsesCRUD extends BaseCRUD {
    
    /**
     * Constructeur
     * 
     * Exemple d'utilisation:
     * $responsesCRUD = new ReviewResponsesCRUD();
     */
    public function __construct($mysqli = null) {
        if ($mysqli === null) {
            require_once CONFIG_PATH . 'coDB.php';
            $mysqli = coDB();
        }
        parent::__construct($mysqli, 'review_responses');
    }
    
  
}