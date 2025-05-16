<?php
require_once CRUD_PATH . '/UsersCRUD.php';

/**
 * Service d'authentification
 * Utilise UsersCRUD pour les opérations d'authentification
 */
class AuthService {
    /** @var UsersCRUD $usersCRUD Instance du CRUD utilisateurs */
    private $usersCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->usersCRUD = new UsersCRUD($mysqli);
        logInfo("AuthService initialized");
    }
    
    /**
     * Enregistre un nouvel utilisateur
     * 
     * @param array $data Données d'enregistrement (username/user_name, email/user_email, password/user_password, phone/user_phone)
     * @return array Statut de l'opération
     */
    public function register($data) {
        logInfo("Register attempt", ['email' => $data['email'] ?? $data['user_email'] ?? 'not provided']);
        
        $email = $data['user_email'] ?? $data['email'] ?? null;
        $password = $data['user_password'] ?? $data['password'] ?? null;
        $username = $data['user_name'] ?? $data['username'] ?? null;
        $phone = $data['user_phone'] ?? $data['phone'] ?? null;
        
        if (!$email || !$password) {
            logError("Register failed - missing credentials");
            return ['status' => 'error', 'message' => 'Email and password are required'];
        }
        
        // Génération automatique du username si vide
        if (!$username) {
            $baseUsername = explode('@', $email)[0];
            $username = $baseUsername;
            
            // Vérification de l'unicité du username généré
            $counter = 1;
            $tempUsername = $username;
            
            while (!empty($this->usersCRUD->get(['id'], ['username' => $tempUsername]))) {
                $tempUsername = $username . $counter;
                $counter++;
            }
            
            $username = $tempUsername;
        }
        
        // Vérification email existant
        $existingEmail = $this->usersCRUD->get(['id'], ['email' => $email]);
        if (!empty($existingEmail)) {
            logError("Register failed - email already exists", ['email' => $email]);
            return ['status' => 'error', 'message' => 'Email already exists'];
        }
        
        // Hachage du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
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
            logInfo("User registered successfully", ['user_id' => $userId]);
            return [
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => ['user_id' => $userId],
                'redirect' => '/user/login'
            ];
        }
        
        logError("Register failed - database error");
        return ['status' => 'error', 'message' => 'Failed to register user'];
    }
    
    /**
     * Connecte un utilisateur
     * 
     * @param array $data Données de connexion (username/user_name/email/user_email, password/user_password)
     * @return array Statut de l'opération
     */
    public function login($data) {
        logInfo("Login attempt", ['user' => $data['email'] ?? $data['username'] ?? 'not provided']);
        
        $usernameOrEmail = $data['user_email'] ?? $data['email'] ?? $data['username'] ?? $data['user_name'] ?? null;
        $password = $data['user_password'] ?? $data['password'] ?? null;
        
        if (!$usernameOrEmail || !$password) {
            logError("Login failed - missing credentials");
            return ['status' => 'error', 'message' => 'Username/email and password are required'];
        }
        
        // Recherche de l'utilisateur par nom d'utilisateur ou email
        $users = $this->usersCRUD->get(
            ['id', 'username', 'email', 'password_hash', 'is_admin'],
            [
                'OR' => [
                    'username' => $usernameOrEmail,
                    'email' => $usernameOrEmail
                ]
            ]
        );

        if (empty($users)) {
            logError("Login failed - user not found", ['user' => $usernameOrEmail]);
            return ['status' => 'error', 'message' => 'User not found'];
        }

        $user = $users[0];
        
        // Vérification du mot de passe
        if (!password_verify($password, $user['password_hash'])) {
            logError("Login failed - invalid password", ['user_id' => $user['id']]);
            return ['status' => 'error', 'message' => 'Invalid credentials'];
        }
        
        // Si l'utilisateur est un administrateur, demander une vérification supplémentaire
        if ($user['is_admin']) {
            logInfo("Admin login - verification required", ['user_id' => $user['id']]);
            $_SESSION['admin'] = 'pending';
            // Génération d'un code de vérification
            $adminCode = $this->generateAdminCode();
            
            return [
                'status' => 'pending',
                'service' => 'Notification',
                'action' => 'sendEmail',
                'data' => [
                    'message' => 'Admin verification required',
                    'email_data' => [
                        'type' => 'admin_verification',
                        'to' => $user['email'],
                        'subject' => 'Code de vérification administrateur',
                        'code' => $adminCode
                    ],
                    'redirect' => '/admin/verify'
                ]
            ];
        }
        
        // Pour un utilisateur normal
        unset($user['password_hash']);
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_id'] = $user['id'];
        
        logInfo("User logged in successfully", ['user_id' => $user['id']]);
        return [
            'status' => 'success', 
            'message' => 'Login successful', 
            'data' => [
                'user' => $user
            ],
            'redirect' => '/user/account'
        ];
    }
    
    
    /**
     * Vérifie le code d'authentification administrateur
     * 
     * @param array $data Données de vérification (verification_code, username/email, password)
     * @return array Statut de l'opération
     */
    public function verifyAdminCode($data) {
        logInfo("Admin verification attempt", ['email' => $data['email'] ?? $data['user_email'] ?? 'not provided']);
        
        $code = $data['verification_code'] ?? null;
        $usernameOrEmail = $data['user_email'] ?? $data['email'] ?? $data['username'] ?? $data['user_name'] ?? null;
        $password = $data['user_password'] ?? $data['password'] ?? null;
        
        if (!$usernameOrEmail || !$password || !$code) {
            logError("Admin verification failed - missing data");
            return ['status' => 'error', 'message' => 'Username/email, password and verification code are required'];
        }
        
        // Recherche de l'utilisateur par nom d'utilisateur ou email
        // Recherche de l'utilisateur par nom d'utilisateur ou email
        $users = $this->usersCRUD->get(
            ['id', 'username', 'email', 'password_hash', 'is_admin'],
            [
                'OR' => [
                    'username' => $usernameOrEmail,
                    'email' => $usernameOrEmail
                ]
            ]
        );
        
        if (empty($users)) {
            logError("Admin verification failed - user not found");
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        $user = $users[0];
        
        // Vérification du mot de passe
        if (!password_verify($password, $user['password_hash'])) {
            logError("Admin verification failed - invalid credentials", ['user_id' => $user['id']]);
            return ['status' => 'error', 'message' => 'Invalid credentials'];
        }
        
        // Vérification que l'utilisateur est bien un admin
        if (!$user['is_admin']) {
            logError("Admin verification failed - not an admin user", ['user_id' => $user['id']]);
            return ['status' => 'error', 'message' => 'Unauthorized access'];
        }

        // Suppression du mot de passe haché des données renvoyées
        unset($user['password_hash']);
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['admin'] = 'admin';
        
        logInfo("Admin verified successfully", ['user_id' => $user['id']]);
        return [
            'status' => 'success', 
            'message' => 'Admin verified successfully', 
            'data' => $user,
            'redirect' => '/admin/dashboard'
        ];
    }
    
    /**
     * Réinitialise le mot de passe d'un utilisateur
     * 
     * @param array $data Données de réinitialisation (email/user_email)
     * @return array Statut de l'opération
     */
    public function resetPassword($data) {
        logInfo("Password reset request", ['email' => $data['email'] ?? $data['user_email'] ?? 'not provided']);
        
        $email = $data['user_email'] ?? $data['email'] ?? null;
        
        if (!$email) {
            return ['status' => 'error', 'message' => 'Email is required'];
        }
        
        // Vérifier si l'utilisateur existe
        $users = $this->usersCRUD->get(['id', 'email'], ['email' => $email]);
        
        if (empty($users)) {
            logError("Password reset failed - user not found");
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        // Génération d'un jeton de réinitialisation
        $resetToken = bin2hex(random_bytes(16));
        $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Mise à jour de l'utilisateur avec le jeton de réinitialisation
        $this->usersCRUD->update(
            ['reset_token' => $resetToken, 'reset_token_expiry' => $expiryTime],
            ['id' => $users[0]['id']]
        );
        
        logInfo("Password reset token generated", ['user_id' => $users[0]['id']]);
        // Envoi d'un email de réinitialisation via le service de notification
        return [
            'status' => 'pending',
            'service' => 'Notification',
            'action' => 'sendEmail',
            'data' => [
                'message' => 'Password reset email sent',
                'email_data' => [
                    'type' => 'password_reset',
                    'to' => $email,
                    'subject' => 'Réinitialisation de mot de passe',
                    'reset_token' => $resetToken
                ],
                'redirect' => '/user/login'
            ]
        ];
    }
    
    /**
     * Vérifie l'email d'un utilisateur
     * 
     * @param array $data Données de vérification (email/user_email)
     * @return array Statut de l'opération
     */
    public function verifyEmail($data) {
        logInfo("Email verification attempt", ['email' => $data['email'] ?? $data['user_email'] ?? 'not provided']);
        
        $email = $data['user_email'] ?? $data['email'] ?? null;
        
        if (!$email) {
            return ['status' => 'error', 'message' => 'Email is required'];
        }
        
        // Vérifier si l'utilisateur existe
        $users = $this->usersCRUD->get(['id'], ['email' => $email]);
        
        if (empty($users)) {
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        // Mise à jour de l'utilisateur pour marquer l'email comme vérifié
        $result = $this->usersCRUD->update(['is_verified' => 1], ['id' => $users[0]['id']]);
        
        if ($result) {
            logError("Email verified successfully", ['user_id' => $users[0]['id']]);
            return ['status' => 'success', 'message' => 'Email verified successfully'];
        }
        
        logInfo("Email verification failed");
        return ['status' => 'error', 'message' => 'Failed to verify email'];
    }
    
    /**
     * Déconnecte un utilisateur
     * 
     * @param array $data Données de déconnexion (non utilisées)
     * @return array Statut de l'opération
     */
    public function logout($data) {
        logInfo("User logout", ['user_id' => $_SESSION['user_id'] ?? 'unknown']);
        session_destroy();
        return ['status' => 'success', 'message' => 'Logged out successfully'];
    }
    
    /**
     * Génère un code de vérification administrateur
     * 
     * @return string Code de vérification
     */
    private function generateAdminCode() {
        return sprintf("%06d", mt_rand(100000, 999999));
    }
}