<?php
require_once __DIR__ . '/BaseCRUD.php';

/*
CREATE TABLE IF NOT EXISTS monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(191) NOT NULL,
    status VARCHAR(50) DEFAULT 'Healthy',
    last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;
 */
class MonitoringCRUD extends BaseCRUD {
    
    /**
     * Constructeur
     */
    public function __construct($mysqli = null) {
        if ($mysqli === null) {
            require_once CONFIG_PATH . 'coDB.php';
            $mysqli = coDB();
        }
        parent::__construct($mysqli, 'monitoring');
    }
    
    
}