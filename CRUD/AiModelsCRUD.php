<?php

/**
 * Classe CRUD pour la gestion des modèles d'IA
 */
class AiModelsCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'ai_models', 'id');
    }
    
    /**
     * Récupère tous les modèles d'IA actifs
     * 
     * @return array Liste des modèles d'IA actifs
     */
    public function getActiveModels() {
        return $this->get(['*'], ['is_active' => true]);
    }
    
    /**
     * Récupère un modèle d'IA par son nom
     * 
     * @param string $modelName Nom du modèle
     * @return array|null Données du modèle ou null si non trouvé
     */
    public function getModelByName($modelName) {
        $results = $this->get(['*'], ['model_name' => $modelName]);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Crée un nouveau modèle d'IA
     * 
     * @param array $modelData Données du modèle
     * @return int|bool ID du modèle créé ou false en cas d'échec
     */
    public function createModel($modelData) {
        // Vérifier si le modèle existe déjà
        $existingModel = $this->getModelByName($modelData['model_name']);
        if ($existingModel) {
            $this->errorInfo = ['message' => 'Un modèle avec ce nom existe déjà'];
            return false;
        }
        
        // Convertir les paramètres en JSON si nécessaire
        if (isset($modelData['parameters']) && is_array($modelData['parameters'])) {
            $modelData['parameters'] = json_encode($modelData['parameters']);
        }
        
        return $this->insert($modelData);
    }
    
    /**
     * Met à jour un modèle d'IA
     * 
     * @param int $modelId ID du modèle
     * @param array $modelData Données du modèle à mettre à jour
     * @return bool Succès de l'opération
     */
    public function updateModel($modelId, $modelData) {
        // Convertir les paramètres en JSON si nécessaire
        if (isset($modelData['parameters']) && is_array($modelData['parameters'])) {
            $modelData['parameters'] = json_encode($modelData['parameters']);
        }
        
        return $this->update($modelId, $modelData);
    }
    
    /**
     * Active ou désactive un modèle d'IA
     * 
     * @param int $modelId ID du modèle
     * @param bool $isActive État d'activation
     * @return bool Succès de l'opération
     */
    public function setModelStatus($modelId, $isActive) {
        return $this->update($modelId, ['is_active' => $isActive ? 1 : 0]);
    }
}