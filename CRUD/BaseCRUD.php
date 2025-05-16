<?php

/**
 * Classe CrudBase améliorée
 * Fournit des opérations CRUD complètes sur n'importe quelle table
 */
class BaseCRUD {
    /** @var mysqli $mysqli Connexion à la base de données */
    protected $mysqli;
    
    /** @var string $table Nom de la table */
    protected $table;
    
    /** @var string $primaryKey Clé primaire de la table */
    protected $primaryKey = 'id';
    
    /** @var array $errorInfo Stocke les informations d'erreur */
    protected $errorInfo = [];
    protected $conn;
    protected array $options = [];


    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     * @param string $table Nom de la table
     * @param string $primaryKey Clé primaire (par défaut 'id')
     */
    public function __construct($mysqli, $table, $primaryKey = 'id', $options = []) {
        // Inclure le fichier de connexion à la base de données
        require_once CONFIG_PATH . 'coDB.php';
    
        // Établir la connexion à la base de données
        $this->conn = coDB();
        $this->table = $table;
        $this->mysqli = $mysqli;
        $this->options = $options;
        $this->table = $this->sanitizeTableName($table);
        $this->primaryKey = $this->sanitizeColumnName($primaryKey);
    }
    
    /**
     * Récupère les données avec pagination, tri et relation
     * 
     * @param array $fields Champs à récupérer
     * @param array $filters Conditions de filtrage
     * @param array $options Options supplémentaires (pagination, tri, jointures)
     * @return array Données récupérées
     */
    public function get(array $fields = ['*'], array $filters = [], array $options = []) {
        try {
            logInfo("Début de la requête GET sur la table: {$this->table}", [
                'fields' => $fields,
                'filters' => $filters,
                'options' => $options
            ]);
            
            // Construction des colonnes avec leurs alias
            $columns = [];
            foreach ($fields as $field) {
                if ($field === '*') {
                    $columns[] = isset($options['table_alias']) ? 
                        "`{$options['table_alias']}`.*" : '*';
                } else {
                    // Préserver les champs avec alias (table.colonne ou AS)
                    if (strpos($field, '.') !== false || strpos($field, ' AS ') !== false || strpos($field, ' as ') !== false) {
                        $columns[] = $field;
                    } else {
                        $column = $this->sanitizeColumnName($field);
                        if (isset($options['table_alias'])) {
                            $columns[] = "`{$options['table_alias']}`.`$column`";
                        } else {
                            $columns[] = "`$column`";
                        }
                    }
                }
            }
            
            $columnsStr = implode(", ", $columns);
            $tableAlias = isset($options['table_alias']) ? " AS `{$options['table_alias']}`" : '';
            $query = "SELECT $columnsStr FROM `$this->table`$tableAlias";
            
            // Gestion des jointures avec leurs alias
            if (isset($options['joins']) && is_array($options['joins'])) {
                foreach ($options['joins'] as $join) {
                    if (isset($join['table'], $join['on'])) {
                        $joinType = $join['type'] ?? 'LEFT';
                        $joinTable = $this->sanitizeTableName($join['table']);
                        $joinAlias = isset($join['table_alias']) ? " AS `{$join['table_alias']}`" : '';
                        $query .= " $joinType JOIN `$joinTable`$joinAlias ON {$join['on']}";
                    }
                }
            }
            
            // Utiliser le buildWhereClause modifié avec les alias
            $params = [];
            $whereClause = $this->buildWhereClause($filters, $params, $options);
            if (!empty($whereClause)) {
                $query .= " WHERE $whereClause";
            }
            
            // Gestion des groupements
            if (isset($options['groupBy'])) {
                $groupBy = $this->sanitizeColumnName($options['groupBy']);
                $query .= " GROUP BY `$groupBy`";
                
                // Having clause
                if (isset($options['having'])) {
                    $query .= " HAVING " . $options['having'];
                }
            }
            
            // Gestion du tri
            if (isset($options['orderBy'])) {
                $orderBy = $this->sanitizeColumnName($options['orderBy']);
                $direction = isset($options['orderDirection']) && strtoupper($options['orderDirection']) === 'DESC' ? 'DESC' : 'ASC';
                $query .= " ORDER BY `$orderBy` $direction";
            }
            
            // Gestion de la pagination
            if (isset($options['limit'])) {
                $limit = (int)$options['limit'];
                $query .= " LIMIT $limit";
                
                if (isset($options['offset'])) {
                    $offset = (int)$options['offset'];
                    $query .= " OFFSET $offset";
                }
            }
            
            // Préparation et exécution de la requête
            $stmt = $this->mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $this->mysqli->error);
            }
            
            // Liaison des paramètres si nécessaire
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            logInfo("Requête GET réussie sur {$this->table}", ['count' => count($data)]);
            return $data;
        } catch (Exception $e) {
            $this->setError('get', $e->getMessage());
            logError("Erreur lors de la requête GET sur {$this->table}", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }
    
    /**
     * Récupère un seul enregistrement
     * 
     * @param mixed $id Identifiant de l'enregistrement
     * @param array $fields Champs à récupérer
     * @return array|null Données de l'enregistrement ou null
     */
    public function find($id, array $fields = ['*']) {
        $filters = [$this->primaryKey => $id];
        $results = $this->get($fields, $filters);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Compte le nombre d'enregistrements
     * 
     * @param array $filters Conditions de filtrage
     * @return int Nombre d'enregistrements
     */
    public function count(array $filters = []) {
        try {
            $query = "SELECT COUNT(*) as count FROM `$this->table`";
            
            // Construction des conditions WHERE
            $params = [];
            $whereClause = $this->buildWhereClause($filters, $params);
            if (!empty($whereClause)) {
                $query .= " WHERE $whereClause";
            }
            
            // Préparation et exécution
            $stmt = $this->mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $this->mysqli->error);
            }
            
            // Liaison des paramètres si nécessaire
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return (int)$row['count'];
        } catch (Exception $e) {
            $this->setError('count', $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Vérifie si des enregistrements correspondent aux critères
     * 
     * @param array $filters Filtres de recherche
     * @param array $compare Valeurs à comparer
     * @return bool Vrai si correspondance
     */
    public function check(array $filters, array $compare) {
        $fields = array_keys($compare);
        $data = $this->get($fields, $filters);
        
        if (empty($data)) {
            return false;
        }
        
        foreach ($data as $row) {
            $match = true;
            foreach ($compare as $key => $value) {
                // Si la clé n'existe pas ou la valeur ne correspond pas
                if (!isset($row[$key]) || $row[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Met à jour des enregistrements
     * 
     * @param array $data Données à mettre à jour
     * @param array $conditions Conditions pour la mise à jour
     * @return bool Succès de l'opération
     */
    public function update(array $data, array $conditions) {
        try {
            logInfo("Début de la mise à jour sur {$this->table}", [
                'data' => $data,
                'conditions' => $conditions
            ]);
            
            if (empty($data)) {
                throw new Exception("Aucune donnée à mettre à jour");
            }
            
            // Préparation des colonnes et valeurs à mettre à jour
            $sets = [];
            $params = [];
            
            foreach ($data as $column => $value) {
                $column = $this->sanitizeColumnName($column);
                $sets[] = "`$column` = ?";
                $params[] = $value;
            }
            
            // Construction de la clause WHERE
            $whereClause = $this->buildWhereClause($conditions, $params);
            if (empty($whereClause)) {
                throw new Exception("Conditions de mise à jour non spécifiées");
            }
            
            $query = "UPDATE `$this->table` SET " . implode(", ", $sets) . " WHERE $whereClause";
            
            // Préparation et exécution
            $stmt = $this->mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $this->mysqli->error);
            }
            
            // Détermination des types de paramètres
            $types = $this->determineParamTypes($params);
            $stmt->bind_param($types, ...$params);
            
            $result = $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            logInfo("Mise à jour réussie sur {$this->table}", ['affectedRows' => $affectedRows]);
            return $result;
        } catch (Exception $e) {
            $this->setError('update', $e->getMessage());
            logError("Erreur lors de la mise à jour sur {$this->table}", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }
    
    /**
     * Supprime des enregistrements
     * 
     * @param array $conditions Conditions pour la suppression
     * @param bool $softDelete Utiliser la suppression logique si true
     * @param string $deletedColumn Colonne pour la suppression logique
     * @return bool Succès de l'opération
     */
    public function delete(array $conditions, bool $softDelete = false, string $deletedColumn = 'deleted_at') {
        try {
            logInfo("Début de la suppression dans {$this->table}", [
                'conditions' => $conditions,
                'softDelete' => $softDelete
            ]);
            
            if (empty($conditions)) {
                throw new Exception("Conditions de suppression non spécifiées");
            }
            
            // Suppression logique
            if ($softDelete) {
                $column = $this->sanitizeColumnName($deletedColumn);
                return $this->update([$column => date('Y-m-d H:i:s')], $conditions);
            }
            
            // Suppression physique
            $params = [];
            $whereClause = $this->buildWhereClause($conditions, $params);
            
            $query = "DELETE FROM `$this->table` WHERE $whereClause";
            
            // Préparation et exécution
            $stmt = $this->mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $this->mysqli->error);
            }
            
            // Liaison des paramètres si nécessaire
            if (!empty($params)) {
                $types = $this->determineParamTypes($params);
                $stmt->bind_param($types, ...$params);
            }
            
            $result = $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            logInfo("Suppression réussie dans {$this->table}", ['affectedRows' => $affectedRows]);
            return $result;
        } catch (Exception $e) {
            $this->setError('delete', $e->getMessage());
            logError("Erreur lors de la suppression dans {$this->table}", [
                'error' => $e->getMessage(),
                'conditions' => $conditions
            ]);
            return false;
        }
    }
    
    /**
     * Insère un nouvel enregistrement
     * 
     * @param array $data Données à insérer
     * @return int|bool ID du nouvel enregistrement ou false
     */
    public function insert(array $data) {
        try {
            logInfo("Début de l'insertion dans {$this->table}", ['data' => $data]);
            
            if (empty($data)) {
                throw new Exception("Aucune donnée à insérer");
            }
            
            $columns = [];
            $placeholders = [];
            $params = [];
            
            foreach ($data as $column => $value) {
                $column = $this->sanitizeColumnName($column);
                $columns[] = "`$column`";
                $placeholders[] = "?";
                $params[] = $value;
            }
            
            $columnsStr = implode(", ", $columns);
            $placeholdersStr = implode(", ", $placeholders);
            
            $query = "INSERT INTO `$this->table` ($columnsStr) VALUES ($placeholdersStr)";
            
            // Préparation et exécution
            $stmt = $this->mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $this->mysqli->error);
            }
            
            // Détermination des types de paramètres
            $types = $this->determineParamTypes($params);
            $stmt->bind_param($types, ...$params);
            
            $result = $stmt->execute();
            $insertId = $this->mysqli->insert_id;
            $stmt->close();
            
            logInfo("Insertion réussie dans {$this->table}", ['insertId' => $insertId]);
            return $result ? $insertId : false;
        } catch (Exception $e) {
            $this->setError('insert', $e->getMessage());
            logError("Erreur lors de l'insertion dans {$this->table}", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }
    
    /**
     * Insère plusieurs enregistrements en une seule requête
     * 
     * @param array $dataArray Tableau de données à insérer
     * @return bool Succès de l'opération
     */
    public function insertBatch(array $dataArray) {
        try {
            if (empty($dataArray)) {
                throw new Exception("Aucune donnée à insérer");
            }
            
            // Vérification que tous les tableaux ont les mêmes clés
            $firstRow = reset($dataArray);
            $columns = array_keys($firstRow);
            
            foreach ($dataArray as $data) {
                if (array_keys($data) !== $columns) {
                    throw new Exception("Toutes les lignes doivent avoir les mêmes colonnes");
                }
            }
            
            // Préparation des colonnes
            $sanitizedColumns = [];
            foreach ($columns as $column) {
                $sanitizedColumns[] = $this->sanitizeColumnName($column);
            }
            
            $columnsStr = implode("`, `", $sanitizedColumns);
            
            // Préparation des placeholders et valeurs
            $allPlaceholders = [];
            $params = [];
            
            foreach ($dataArray as $data) {
                $rowPlaceholders = [];
                foreach ($columns as $column) {
                    $rowPlaceholders[] = "?";
                    $params[] = $data[$column];
                }
                $allPlaceholders[] = "(" . implode(", ", $rowPlaceholders) . ")";
            }
            
            $placeholdersStr = implode(", ", $allPlaceholders);
            
            // Construction de la requête
            $query = "INSERT INTO `$this->table` (`$columnsStr`) VALUES $placeholdersStr";
            
            // Préparation et exécution
            $stmt = $this->mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $this->mysqli->error);
            }
            
            // Détermination des types de paramètres
            $types = $this->determineParamTypes($params);
            $stmt->bind_param($types, ...$params);
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            $this->setError('insertBatch', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exécute une requête personnalisée
     * 
     * @param string $query Requête SQL
     * @param array $params Paramètres pour la requête préparée
     * @param string $types Types des paramètres (i=integer, d=double, s=string, b=blob)
     * @return array|bool Résultats ou statut de l'opération
     */
    public function query(string $query, array $params = [], string $types = '') {
        try {
            $stmt = $this->mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $this->mysqli->error);
            }
            
            // Liaison des paramètres si nécessaire
            if (!empty($params)) {
                if (empty($types)) {
                    $types = $this->determineParamTypes($params);
                }
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            
            // Vérifier s'il s'agit d'une requête SELECT
            if (stripos(trim($query), 'SELECT') === 0) {
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                return $data;
            } else {
                $affectedRows = $stmt->affected_rows;
                $stmt->close();
                return true;
            }
        } catch (Exception $e) {
            $this->setError('query', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Commence une transaction
     * 
     * @return bool Succès de l'opération
     */
    public function beginTransaction() {
        return $this->mysqli->begin_transaction();
    }
    
    /**
     * Valide une transaction
     * 
     * @return bool Succès de l'opération
     */
    public function commit() {
        return $this->mysqli->commit();
    }
    
    /**
     * Annule une transaction
     * 
     * @return bool Succès de l'opération
     */
    public function rollback() {
        return $this->mysqli->rollback();
    }
    
    /**
     * Récupère la dernière erreur
     * 
     * @return array Informations sur l'erreur
     */
    public function getError() {
        return $this->errorInfo;
    }
    
    /**
     * Récupère le dernier message d'erreur
     * 
     * @return string Message d'erreur
     */
    public function getErrorMessage() {
        return isset($this->errorInfo['message']) ? $this->errorInfo['message'] : '';
    }
    
    /**
     * Définit une erreur
     * 
     * @param string $operation Opération qui a causé l'erreur
     * @param string $message Message d'erreur
     */
    protected function setError(string $operation, string $message) {
        $this->errorInfo = [
            'operation' => $operation,
            'message' => $message,
            'time' => date('Y-m-d H:i:s'),
            'sql_error' => $this->mysqli->error,
            'sql_errno' => $this->mysqli->errno
        ];
    }
    
    /**
     * Construit une clause WHERE à partir d'un tableau de conditions
     * 
     * @param array $filters Conditions de filtrage
     * @param array &$params Paramètres pour requête préparée (référence)
     * @param array $options Options supplémentaires
     * @return string Clause WHERE
     */
    protected function buildWhereClause(array $filters, array &$params, array $options = []) {
        if (empty($filters)) {
            return '';
        }
        
        $conditions = [];
        $tableAlias = isset($options['table_alias']) ? $options['table_alias'] : '';
        
        foreach ($filters as $key => $value) {
            if ($key === 'OR' && is_array($value)) {
                // Gestion des conditions OR
                $orConditions = [];
                foreach ($value as $orKey => $orValue) {
                    $orConditions[] = $this->buildCondition($orKey, $orValue, $params, $tableAlias);
                }
                if (!empty($orConditions)) {
                    $conditions[] = '(' . implode(' OR ', $orConditions) . ')';
                }
                continue;
            }
            
            $conditions[] = $this->buildCondition($key, $value, $params, $tableAlias);
        }
        
        return implode(' AND ', $conditions);
    }
    
    /**
     * Construit une condition pour la clause WHERE
     * 
     * @param string $key Clé de la condition
     * @param mixed $value Valeur de la condition
     * @param array &$params Paramètres pour requête préparée (référence)
     * @param string $tableAlias Alias de la table
     * @return string Condition construite
     */
    protected function buildCondition($key, $value, &$params, $tableAlias = '') {
        // Si la clé contient déjà un point (alias de table), la laisser telle quelle
        if (strpos($key, '.') !== false) {
            $params[] = $value;
            return "$key = ?";
        }
        
        // Sinon, ajouter l'alias de table si disponible
        $column = $this->sanitizeColumnName($key);
        if ($tableAlias) {
            $condition = "`$tableAlias`.`$column` = ?";
        } else {
            $condition = "`$column` = ?";
        }
        
        $params[] = $value;
        return $condition;
    }
    
    /**
     * Détermine les types de paramètres pour bind_param
     * 
     * @param array $params Tableau de paramètres
     * @return string Types de paramètres (i, d, s, b)
     */
    protected function determineParamTypes(array $params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                // Par défaut considérer comme string
                $types .= 's';
            }
        }
        return $types;
    }
    
    /**
     * Sanitize le nom d'une table
     * 
     * @param string $tableName Nom de la table
     * @return string Nom de table sanitisé
     */
    protected function sanitizeTableName(string $tableName) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    }
    
    /**
     * Sanitize le nom d'une colonne
     * 
     * @param string $columnName Nom de la colonne
     * @return string Nom de colonne sanitisé
     */
    protected function sanitizeColumnName(string $columnName) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
    }
}

/**
 * Exemple d'utilisation
 */
/*
$mysqli = new mysqli("localhost", "user", "password", "database");

// Initialisation du gestionnaire CRUD pour la table utilisateurs
$usersCrud = new CrudBase($mysqli, "users", "user_id");

// Récupérer tous les utilisateurs actifs
$activeUsers = $usersCrud->get(["user_id", "username", "email"], ["status" => "active"]);

// Récupérer un utilisateur par ID
$user = $usersCrud->find(5);

// Insérer un nouvel utilisateur
$newUserId = $usersCrud->insert([
    "username" => "john_doe",
    "email" => "john@example.com",
    "password" => password_hash("secret123", PASSWORD_DEFAULT),
    "created_at" => date("Y-m-d H:i:s")
]);

// Mettre à jour un utilisateur
$usersCrud->update(
    ["last_login" => date("Y-m-d H:i:s")],
    ["user_id" => 5]
);

// Supprimer un utilisateur
$usersCrud->delete(["user_id" => 3]);

// Recherche avancée avec pagination et tri
$options = [
    "limit" => 10,
    "offset" => 0,
    "orderBy" => "created_at",
    "orderDirection" => "DESC",
    "joins" => [
        [
            "type" => "LEFT",
            "table" => "user_profiles",
            "on" => "users.user_id = user_profiles.user_id"
        ]
    ],
    "groupBy" => "role"
];

$filters = [
    "role" => [
        "operator" => "IN",
        "value" => ["admin", "editor"]
    ],
    "last_login" => [
        "operator" => ">=",
        "value" => "2023-01-01"
    ]
];

$results = $usersCrud->get(["users.*", "user_profiles.bio"], $filters, $options);
*/