<?php
require_once __DIR__ . '/BaseCRUD.php';

/*
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE = InnoDB;
 */
class OrderItemsCRUD extends BaseCRUD {
    
    /**
     * Constructeur
     */
    public function __construct($mysqli = null) {
        if ($mysqli === null) {
            require_once CONFIG_PATH . 'coDB.php';
            $mysqli = coDB();
        }
        parent::__construct($mysqli, 'order_items');
    }
    
}