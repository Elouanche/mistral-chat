<?php
require_once CRUD_PATH . '/AiRequestsCRUD.php';
require_once CRUD_PATH . '/AiResponsesCRUD.php';
require_once CRUD_PATH . '/ApiUsageCRUD.php';
require_once SHARED_PATH . '/apiRequest.php';

/**
 * Service pour l'interaction avec l'API Mistral
 */
class MistralApiService {
    /** @var AiRequestsCRUD $requestsCRUD Instance du CRUD pour les requêtes */
    private $requestsCRUD;
    
    /** @var AiResponsesCRUD $responsesCRUD Instance du CRUD pour les réponses */
    private $responsesCRUD;
    
    /** @var ApiUsageCRUD $usageCRUD Instance du CRUD pour l'utilisation */
    private $usageCRUD;
    
    /** @var string $apiKey Clé API Mistral */
    private $apiKey;
    
    /** @var string $apiEndpoint Endpoint de l'API Mistral */
    private $apiEndpoint = 'https://api.mistral.ai/v1/chat/completions';
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     * @param string|null $apiKey Clé API Mistral (si null, utilise la clé par défaut)
     */
    public function __construct($mysqli, $apiKey = null) {
        $this->requestsCRUD = new AiRequestsCRUD($mysqli);
        $this->responsesCRUD = new AiResponsesCRUD($mysqli);
        $this->usageCRUD = new ApiUsageCRUD($mysqli);
        
        // Utiliser la clé API fournie ou récupérer celle par défaut
        $this->apiKey = $apiKey ?? get_env_variable('MISTRAL_API_KEY');
        
        logInfo("MistralApiService initialized");
    }
    
    /**
     * Envoie une requête à l'API Mistral
     * 
     * @param array $data Données de la requête
     * @return array Résultat de l'opération
     */
    public function sendChatRequest($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['user_id']) || !isset($data['model_id']) || !isset($data['messages'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur, l'ID du modèle et les messages sont obligatoires"
                ];
            }
            
            // Récupérer le nom du modèle à partir de l'ID
            $modelName = $this->getModelName($data['model_id']);
            if (!$modelName) {
                return [
                    'status' => 'error',
                    'message' => "Modèle non trouvé ou non actif"
                ];
            }
            
            // Préparer les paramètres de la requête
            $requestParams = [
                'model' => $modelName,
                'messages' => $data['messages'],
                'temperature' => $data['temperature'] ?? 0.7,
                'max_tokens' => $data['max_tokens'] ?? 1000,
                'top_p' => $data['top_p'] ?? 1.0,
                'stream' => $data['stream'] ?? false
            ];
            
            // Enregistrer la requête dans la base de données
            $requestData = [
                'user_id' => $data['user_id'],
                'conversation_id' => $data['conversation_id'] ?? null,
                'model_id' => $data['model_id'],
                'prompt' => json_encode($data['messages']),
                'parameters' => json_encode($requestParams),
                'status' => 'pending'
            ];
            
            $requestId = $this->requestsCRUD->createRequest($requestData);
            
            if (!$requestId) {
                return [
                    'status' => 'error',
                    'message' => "Erreur lors de l'enregistrement de la requête"
                ];
            }
            
            // Préparer les en-têtes de la requête
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ];
            
            // Envoyer la requête à l'API Mistral
            $startTime = microtime(true);
            $response = apiRequest($this->apiEndpoint, 'POST', $requestParams, $headers);
            $endTime = microtime(true);
            $latency = round(($endTime - $startTime) * 1000); // en millisecondes
            
            // Mettre à jour la requête avec le statut et la latence
            $updateData = [
                'response_timestamp' => date('Y-m-d H:i:s'),
                'latency_ms' => $latency,
                'status' => isset($response['error']) ? 'failed' : 'completed',
                'error_message' => isset($response['error']) ? $response['error']['message'] : null
            ];
            
            $this->requestsCRUD->updateRequest($requestId, $updateData);
            
            // En cas d'erreur dans la réponse
            if (isset($response['error'])) {
                return [
                    'status' => 'error',
                    'message' => $response['error']['message'] ?? "Erreur lors de la communication avec l'API Mistral"
                ];
            }
            
            // Enregistrer la réponse dans la base de données
            $responseData = [
                'request_id' => $requestId,
                'response_text' => $response['choices'][0]['message']['content'] ?? '',
                'finish_reason' => $response['choices'][0]['finish_reason'] ?? null,
                'raw_response' => json_encode($response)
            ];
            
            $responseId = $this->responsesCRUD->createResponse($responseData);
            
            // Enregistrer l'utilisation des tokens
            if (isset($response['usage'])) {
                $usageData = [
                    'user_id' => $data['user_id'],
                    'model_id' => $data['model_id'],
                    'request_id' => $requestId,
                    'input_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                    'output_tokens' => $response['usage']['completion_tokens'] ?? 0,
                    'estimated_cost' => $this->calculateCost($modelName, $response['usage']),
                    'usage_date' => date('Y-m-d')
                ];
                
                $this->usageCRUD->recordUsage($usageData);
                
                // Mettre à jour la requête avec les tokens utilisés
                $this->requestsCRUD->updateRequest($requestId, [
                    'tokens_prompt' => $response['usage']['prompt_tokens'] ?? 0,
                    'tokens_completion' => $response['usage']['completion_tokens'] ?? 0
                ]);
            }
            
            // Préparer la réponse pour le client
            $result = [
                'status' => 'success',
                'data' => [
                    'message' => $response['choices'][0]['message'] ?? null,
                    'finish_reason' => $response['choices'][0]['finish_reason'] ?? null,
                    'usage' => $response['usage'] ?? null,
                    'request_id' => $requestId
                ]
            ];
            
            return $result;
        } catch (Exception $e) {
            logError("Erreur lors de l'envoi de la requête à l'API Mistral", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la communication avec l'API Mistral: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupère le nom du modèle à partir de son ID
     * 
     * @param int $modelId ID du modèle
     * @return string|null Nom du modèle ou null si non trouvé
     */
    private function getModelName($modelId) {
        // Créer une instance temporaire du CRUD des modèles
        $modelsCRUD = new AiModelsCRUD($this->requestsCRUD->mysqli);
        $models = $modelsCRUD->get(['model_name'], ['id' => $modelId, 'is_active' => 1]);
        
        return !empty($models) ? $models[0]['model_name'] : null;
    }
    
    /**
     * Calcule le coût estimé d'une requête
     * 
     * @param string $modelName Nom du modèle
     * @param array $usage Données d'utilisation
     * @return float Coût estimé
     */
    private function calculateCost($modelName, $usage) {
        // Prix par 1000 tokens (à ajuster selon les tarifs actuels de Mistral)
        $pricingPerModel = [
            'mistral-tiny' => ['input' => 0.15, 'output' => 0.45],
            'mistral-small' => ['input' => 0.60, 'output' => 1.80],
            'mistral-medium' => ['input' => 2.50, 'output' => 7.50],
            'mistral-large' => ['input' => 8.00, 'output' => 24.00],
            'default' => ['input' => 0.50, 'output' => 1.50]
        ];
        
        $pricing = $pricingPerModel[$modelName] ?? $pricingPerModel['default'];
        
        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;
        
        $inputCost = ($inputTokens / 1000) * $pricing['input'];
        $outputCost = ($outputTokens / 1000) * $pricing['output'];
        
        return $inputCost + $outputCost;
    }
}