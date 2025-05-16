<?php
define('LOG_LEVELS', [
    'INFO'    => 'INFO',
    'WARNING' => 'WARNING',
    'ERROR'   => 'ERROR',
    'DEBUG'   => 'DEBUG'
]);

define('LOG_DIRECTORY', __DIR__ . '/../logs');
define('LOG_FILE', LOG_DIRECTORY . '/api_log.json');
define('LOG_INFO_FILE', LOG_DIRECTORY . '/api_info.log');

function fmtLog($lvl, $msg, $ctx = []) {
    // S’assurer que $ctx est un tableau
    if (!is_array($ctx)) {
        $ctx = ['value' => $ctx];
    }

    // Nettoyage des chaînes UTF-8
    array_walk_recursive($ctx, function(&$v) {
        if (is_string($v)) {
            $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
        }
    });

    $entry = [
        'time' => date('Y-m-d H:i:s'),
        'lvl'  => $lvl,
        'msg'  => $msg,
    ];
    if (!empty($_SERVER['REQUEST_METHOD'])) {
        $entry['method'] = $_SERVER['REQUEST_METHOD'];
        $entry['uri']    = $_SERVER['REQUEST_URI'];
        $entry['ip']     = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    if (isset($_SESSION['user_id'])) {
        $entry['uid'] = $_SESSION['user_id'];
    }
    if (!empty($ctx)) {
        $entry['ctx'] = $ctx;
    }
    return $entry;
}


/**
 * Ajoute une entrée JSON dans LOG_FILE, en assurant :
 * - Au premier appel : création de "[\n<json>\n]"
 * - Aux appels suivants : insertion de ",<json>\n" juste avant le "]"
 */
function writeLog($lvl, $msg, $ctx = []) {
    if (!is_dir(LOG_DIRECTORY)) {
        mkdir(LOG_DIRECTORY, 0755, true);
    }

    $jsonEntry = json_encode(fmtLog($lvl, $msg, $ctx), JSON_UNESCAPED_UNICODE);

    // Si le fichier n'existe pas ou est vide, on crée le tableau
    if (!file_exists(LOG_FILE) || filesize(LOG_FILE) === 0) {
        $initial = "[\n" . $jsonEntry . "]";
        file_put_contents(LOG_FILE, $initial);
    } else {
        // On insère le ",<entry>\n" avant le dernier caractère ']'
        $fp = fopen(LOG_FILE, 'c+');
        // Aller juste avant la fin
        fseek($fp, -1, SEEK_END);
        // Écrire la virgule, l'entrée, puis la fermeture
        fwrite($fp, ",\n" . $jsonEntry . "]");
        fclose($fp);
    }

    return fmtLog($lvl, $msg, $ctx);
}
function logInfo($msg, $ctx = []) {
    if (!is_dir(LOG_DIRECTORY)) {
        mkdir(LOG_DIRECTORY, 0755, true);
    }

    // Transformer $ctx en chaîne avec flèches -> entre chaque élément (clé + valeur)
    $ctxString = '';
    if (!empty($ctx)) {
        $formatted = [];
        foreach ($ctx as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $formatted[] = "$key: $value";
        }
        $ctxString = ' -> ' . implode(' -> ', $formatted);
    }

    $line = "[" . date('Y-m-d H:i:s') . "] " . $msg . $ctxString . PHP_EOL;
    file_put_contents(LOG_INFO_FILE, $line, FILE_APPEND);

    return $line;
}
// Fonction utilitaire pour lire les logs info fusionnés
function logPath() {
    if (!file_exists(LOG_INFO_FILE)) return '';
    $ctx = [];
    // Lire le contenu
    $contents = file_get_contents(LOG_INFO_FILE);

    // Vider le fichier
    file_put_contents(LOG_INFO_FILE, '');
    
    return writeLog(LOG_LEVELS['INFO'], $contents, $ctx);;
}
function logWarning($msg, $ctx = []) {
    logPath();
    return writeLog(LOG_LEVELS['WARNING'], $msg, $ctx);
}

function logError($msg, $ctx = []) {
    return writeLog(LOG_LEVELS['ERROR'], $msg, $ctx);
}

// DEBUG comme WARNING avec tag
function logDebug($msg, $ctx = []) {
    //writeLog(LOG_LEVELS['WARNING'], '[DEBUG] ' . $msg, $ctx);
    return ;
}

// Lecture & purge du log info
function getInfoLog() {
    if (!file_exists(LOG_INFO_FILE)) {
        return '';
    }
    $c = file_get_contents(LOG_INFO_FILE);
    file_put_contents(LOG_INFO_FILE, '');
    return $c;
}
