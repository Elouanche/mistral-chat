<?php
require_once __DIR__ . '/BaseCRUD.php';

/*
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'unread',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB;
 */
class NotificationsCRUD extends BaseCRUD {
    
    /**
     * Constructeur
     */
    public function __construct($mysqli = null) {
        if ($mysqli === null) {
            require_once CONFIG_PATH . 'coDB.php';
            $mysqli = coDB();
        }
        parent::__construct($mysqli, 'notifications');
    }
    
   
}