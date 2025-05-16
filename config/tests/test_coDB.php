<?php
// a changer
require_once '../../config/coDB.php';

function test_coDB() {
    try {
        $conn = coDB();
        if ($conn->connect_error) {
            return [
                'success' => false,
                'reason' => "Échec de la connexion : " . $conn->connect_error,
            ];
        }
        return [
            'success' => true,
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'reason' => "Exception lors de la connexion : " . $e->getMessage(),
        ];
    }
}

return test_coDB();
?>