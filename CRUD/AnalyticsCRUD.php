<?php
require_once __DIR__ . '/BaseCRUD.php';

/*
CREATE TABLE IF NOT EXISTS analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(191) NOT NULL,
    value DECIMAL(10, 2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;
 */
class AnalyticsCRUD extends BaseCRUD {
    
    /**
     * Constructeur
     */
    public function __construct($mysqli = null) {
        if ($mysqli === null) {
            require_once CONFIG_PATH . 'coDB.php';
            $mysqli = coDB();
        }
        parent::__construct($mysqli, 'analytics');
    }
 
}