<?php
// Liste des messages d'erreur avec leur code HTTP associé
const ERROR_MESSAGES = [
    'INVALID_METHOD' => ['message' => 'Méthode non autorisée', 'code' => 405],
    'INVALID_JSON' => ['message' => 'JSON invalide', 'code' => 400],
    'UNSUPPORTED_CONTENT_TYPE' => ['message' => 'Content-type non supporté', 'code' => 415],
    'NO_ACTION' => ['message' => 'Action non spécifiée', 'code' => 400],
    'INVALID_ACTION' => ['message' => 'Action invalide', 'code' => 400],
    'INVALID_PARAM' => ['message' => 'Paramètre invalide', 'code' => 400],
    'UPLOAD_FAILED' => ['message' => 'Erreur lors de l\'upload du fichier', 'code' => 500],
    'FILE_TOO_LARGE' => ['message' => 'Le fichier est trop grand', 'code' => 413],
    'INVALID_FILE_FORMAT' => ['message' => 'Format de fichier non supporté', 'code' => 415],
    'INVALID_TRANSFER_ACTION' => ['message' => 'Action de transfert invalide', 'code' => 400],
    'MISSING_PARAMETERS' => ['message' => 'Paramètres manquants', 'code' => 400],
    'INVALID_NOTIFICATION_TYPE' => ['message' => 'Type de notification invalide', 'code' => 400],
    'FRIEND_REQUEST_FAILED' => ['message' => 'Échec de la création de la demande d\'ami', 'code' => 500],
    'PARTICIPANT_REQUEST_FAILED' => ['message' => 'Échec de l\'ajout du participant', 'code' => 500],
    'CONVERSION_FAILED' => ['message' => 'Erreur lors de la conversion de l\'image en WebP.', 'code' => 500],
    'INTERNAL_ERROR' => ['message' => 'Erreur interne du serveur', 'code' => 500],
    'UNAUTHORIZED' => ['message' => 'Non autorisé', 'code' => 401],
    'NOT_FOUND' => ['message' => 'Page non trouvée', 'code' => 404],
    
    'INVALID_INPUT' => ['message' => 'Page non trouvée', 'code' => 400],
    
];


function sendErrorResponse($Erreur, $isAdmin = false) {
    $error = ERROR_MESSAGES[$Erreur] ?? ['message' => 'Erreur inconnue', 'code' => 500];
    http_response_code($error['code']);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $error['message'],
        'code' => $error['code'],
        'isAdmin' => $isAdmin
    ]);
    exit;
}

?>