<?php
require_once CRUD_PATH . '/UsersCRUD.php';

/**
 * Service de gestion des utilisateurs
 * Utilise UsersCRUD pour les opérations de base de données
 */
class UserService {
    /** @var UsersCRUD $usersCRUD Instance du CRUD utilisateurs */
    private $usersCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->usersCRUD = new UsersCRUD($mysqli);
    }
    
    /**
     * Crée un nouvel utilisateur
     * 
     * @param array $data Données de l'utilisateur (username, email, password, phone)
     * @return array Statut de l'opération
     */
    public function createUser($data) {
        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $phone = $data['phone'] ?? null;
        
        if (!$username || !$email || !$password) {
            return ['status' => 'error', 'message' => 'Username, email and password are required'];
        }
        
        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->usersCRUD->get(['id'], ['username' => $username]);
        if (!empty($existingUser)) {
            return ['status' => 'error', 'message' => 'Username already exists'];
        }
        
        $existingEmail = $this->usersCRUD->get(['id'], ['email' => $email]);
        if (!empty($existingEmail)) {
            return ['status' => 'error', 'message' => 'Email already exists'];
        }
        
        // Hachage du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Préparation des données pour l'insertion
        $userData = [
            'username' => $username,
            'email' => $email,
            'password_hash' => $hashedPassword
        ];
        
        if ($phone) {
            $userData['phone'] = $phone;
        }
        
        // Insertion de l'utilisateur
        $userId = $this->usersCRUD->insert($userData);
        
        if ($userId) {
            return [
                'status' => 'success', 
                'message' => 'User created successfully', 
                'data' => ['user_id' => $userId]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to create user'];
    }
    
    /**
     * Met à jour un utilisateur existant
     * 
     * @param array $data Données de l'utilisateur (user_id, username, email, phone)
     * @return array Statut de l'opération
     */
    public function updateUser($data) {
        logInfo("Updating user profile", ['user_id' => $data['user_id'] ?? null]);
        
        $userId = $data['user_id'] ?? null;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Vérifier si l'utilisateur existe
        $user = $this->usersCRUD->find($userId);
        if (!$user) {
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        $updates = [];
        
        // Mise à jour du nom d'utilisateur si fourni
        if (isset($data['username'])) {
            // Vérifier si le nom d'utilisateur est déjà pris par un autre utilisateur
            $existingUser = $this->usersCRUD->get(['id'], [
                'username' => $data['username'],
                'id' => ['operator' => '!=', 'value' => $userId]
            ]);
            
            if (!empty($existingUser)) {
                return ['status' => 'error', 'message' => 'Username already exists'];
            }
            
            $updates['username'] = $data['username'];
        }
        
        // Mise à jour de l'email si fourni
        if (isset($data['email'])) {
            // Vérifier si l'email est déjà pris par un autre utilisateur
            $existingEmail = $this->usersCRUD->get(['id'], [
                'email' => $data['email'],
                'id' => ['operator' => '!=', 'value' => $userId]
            ]);
            
            if (!empty($existingEmail)) {
                return ['status' => 'error', 'message' => 'Email already exists'];
            }
            
            $updates['email'] = $data['email'];
        }
        
        // Mise à jour du mot de passe si fourni
        if (isset($data['password']) && !empty($data['password'])) {
            $updates['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Mise à jour du téléphone si fourni
        if (isset($data['phone'])) {
            $updates['phone'] = $data['phone'];
        }
        
        if (empty($updates)) {
            return ['status' => 'error', 'message' => 'No fields to update'];
        }
        
        // Mise à jour de l'utilisateur
        $result = $this->usersCRUD->update($updates, ['id' => $userId]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'User updated successfully'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to update user'];
    }
    
    /**
     * Supprime un utilisateur
     * 
     * @param array $data Données de l'utilisateur (user_id)
     * @return array Statut de l'opération
     */
    public function deleteUser($data) {
        logInfo("Deleting user", ['user_id' => $data['user_id'] ?? null]);
        
        $userId = $data['user_id'] ?? null;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Vérifier si l'utilisateur existe
        $user = $this->usersCRUD->find($userId);
        if (!$user) {
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        // Suppression de l'utilisateur
        $result = $this->usersCRUD->delete(['id' => $userId]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'User deleted successfully'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to delete user'];
    }
    
    /**
     * Récupère les informations d'un utilisateur
     * 
     * @param array $data Données de l'utilisateur (user_id)
     * @return array Informations de l'utilisateur
     */
    public function getUser($data) {
        logInfo("Getting user details", ['user_id' => $data['user_id'] ?? null]);
        
        $userId = $data['user_id'] ?? null;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Récupération de l'utilisateur sans le mot de passe
        $user = $this->usersCRUD->find($userId, ['id', 'username', 'email', 'phone', 'is_admin', 'created_at']);
        
        if ($user) {
            return ['status' => 'success', 'message' => 'User retrieved successfully', 'data' => $user];
        }
        
        return ['status' => 'error', 'message' => 'User not found'];
    }
    
    /**
     * Liste les utilisateurs avec pagination
     * 
     * @param array $data Données de pagination (page, limit)
     * @return array Liste des utilisateurs
     */
    public function listUsers($data) {
        logInfo("Listing users", [
            'page' => $data['page'] ?? 1,
            'limit' => $data['limit'] ?? 10
        ]);
        
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Récupération des utilisateurs sans les mots de passe
        $users = $this->usersCRUD->get(
            ['id', 'username', 'email', 'phone', 'is_admin', 'created_at'],
            [],
            ['limit' => $limit, 'offset' => $offset]
        );
        
        // Comptage du nombre total d'utilisateurs pour la pagination
        $total = $this->usersCRUD->count();
        
        return [
            'status' => 'success', 
            'message' => 'Users retrieved successfully', 
            'data' => [
                'users' => $users,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ];
    }
    
    /**
     * Met à jour les préférences d'un utilisateur
     * 
     * @param array $data Données de l'utilisateur (user_id, preferences)
     * @return array Statut de l'opération
     */
    public function updateUserPreferences($data) {
        logInfo("Updating user preferences", ['user_id' => $data['user_id'] ?? null]);
        
        $userId = $data['user_id'] ?? null;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Vérifier si l'utilisateur existe
        $user = $this->usersCRUD->find($userId);
        if (!$user) {
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        $preferences = $data['preferences'] ?? null;
        
        if (!$preferences) {
            return ['status' => 'error', 'message' => 'Preferences are required'];
        }
        
        // Mise à jour des préférences
        $result = $this->usersCRUD->update(['preferences' => json_encode($preferences)], ['id' => $userId]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'User preferences updated successfully'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to update user preferences'];
    }
    
    /**
     * Récupère les statistiques d'un utilisateur
     * 
     * @param array $data Données de l'utilisateur (user_id)
     * @return array Statistiques de l'utilisateur
     */
    
}