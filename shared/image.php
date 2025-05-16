<?php
// Gestion centraliser d'upload d'image
/*
define('MAX_FILE_SIZE', 10000000);
*/
/*
function handle_image_upload($file, $target_dir = "../upload/") {
        if (filesize($file['tmp_name']) > MAX_FILE_SIZE) {
            sendErrorResponse(400, ERROR_MESSAGES['FILE_TOO_LARGE']);
        }
    
        if ($file['error'] !== UPLOAD_ERR_OK) {
            sendErrorResponse(500, ERROR_MESSAGES['UPLOAD_FAILED']);
        }
    
        $allowed_types = ["jpg", "jpeg", "png", "gif"];
        $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($imageFileType, $allowed_types)) {
            sendErrorResponse(400, ERROR_MESSAGES['INVALID_FILE_FORMAT']);
        }
    
        // Vérification du type MIME réel du fichier
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    
        $allowed_mime_types = [
            "image/jpeg" => "jpeg",
            "image/jpg" => "jpg",
            "image/png" => "png",
            "image/gif" => "gif",
        ];
    
        if (!isset($allowed_mime_types[$mime_type]) || $allowed_mime_types[$mime_type] !== $imageFileType) {
            sendErrorResponse(400, ERROR_MESSAGES['INVALID_FILE_FORMAT']);
        }
    
        // Vérification et création du répertoire si nécessaire
        if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true)) {
            sendErrorResponse(500, "Impossible de créer le répertoire de destination.");
        }
    
        // Nom unique pour le fichier téléchargé
        $unique_name = uniqid();
        $original_file_name = $unique_name . '.' . $imageFileType;
        $webp_file_name = $unique_name . '.webp';
    
        $target_file = $target_dir . $original_file_name;
        $webp_file = $target_dir . $webp_file_name;
    
        // Déplacement du fichier vers le répertoire cible
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Conversion du fichier en WebP
            if (!convert_to_webp($target_file, $webp_file, $imageFileType)) {
                unlink($target_file);
                sendErrorResponse(500, ERROR_MESSAGES['CONVERSION_FAILED']);
            }
            // Suppression de l'original après conversion
            unlink($target_file);
    
            // Retourner le chemin relatif du fichier WebP
            return './upload/' . $webp_file_name;  // Chemin relatif incluant ./upload/
        } else {
            sendErrorResponse(500, "Erreur inconnue lors du déplacement du fichier.");
        }
    
        return false;
    }*/
    /*    function convert_to_webp($source_file, $destination_file, $image_type) {
        switch ($image_type) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($source_file);
                break;
            case 'png':
                $image = imagecreatefrompng($source_file);
                break;
            case 'gif':
                $image = imagecreatefromgif($source_file);
                break;
            default:
                return false;
        }
        if ($image === false) {
            return false;
        }
        //$success = imagewebp($image, $destination_file);
        //imagedestroy($image);
        return $success;
    }*/
?>