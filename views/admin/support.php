<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Vérification de l'authentification admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'admin') {
    header('Location: /user/login');
    exit;
}


if (strpos($_SERVER['REQUEST_URI'], '/admin/') === 0) {
    require_once SHARED_PATH .'adminMiddleware.php';
    checkAdminAccess();
}

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";

require_once SHARED_PATH . "apiRequest.php";

// Appel à l'API Gateway pour récupérer les conversations
$conversations = makeApiRequest('Support', 'getConversations');

// Récupération des détails d'une conversation si un ID est spécifié
$selectedConversation = null;
$messages = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $conversationId = intval($_GET['id']);
    
    // Appel à l'API Gateway pour récupérer les détails de la conversation
    $conversationDetails = makeApiRequest('Support', 'getConversationDetails', [
        'conversation_id' => $conversationId
    ]);
    
    if (!empty($conversationDetails)) {
        $selectedConversation = $conversationDetails['conversation'];
        $messages = $conversationDetails['messages'];
    }
}
?>

<main class="admin-support">
    <h1>Gestion du Support Client</h1>
    
    <div class="support-container">
        <div class="conversations-list">
            <h2>Conversations</h2>
            <?php if (empty($conversations)): ?>
                <p>Aucune conversation de support trouvée.</p>
            <?php else: ?>
                <table class="support-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Sujet</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conversations as $conv): ?>
                            <tr class="<?php echo $conv['needs_response'] ? 'needs-response' : ''; ?>">
                                <td><?php echo htmlspecialchars($conv['username']); ?></td>
                                <td><?php echo htmlspecialchars($conv['subject']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($conv['last_message'])); ?></td>
                                <td>
                                    <?php if ($conv['needs_response']): ?>
                                        <span class="status-badge urgent">En attente</span>
                                    <?php else: ?>
                                        <span class="status-badge">Répondu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?id=<?php echo $conv['id']; ?>" class="button-small">Voir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <?php if ($selectedConversation): ?>
        <div class="conversation-details">
            <h2>Conversation avec <?php echo htmlspecialchars($selectedConversation['username']); ?></h2>
            <h3>Sujet: <?php echo htmlspecialchars($selectedConversation['subject']); ?></h3>
            
            <div class="messages-container">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['is_admin'] ? 'admin-message' : 'user-message'; ?>">
                        <div class="message-header">
                            <span class="sender"><?php echo $msg['is_admin'] ? 'Admin' : htmlspecialchars($msg['username']); ?></span>
                            <span class="time"><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></span>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form id="replyForm" class="reply-form">
                <input type="hidden" name="conversation_id" value="<?php echo $selectedConversation['id']; ?>">
                <textarea name="message" placeholder="Votre réponse..." required></textarea>
                <button type="submit" class="button-spe">Envoyer la réponse</button>
            </form>
        </div>
        <?php else: ?>
            <div class="conversation-details empty-state">
                <p>Sélectionnez une conversation pour voir les détails.</p>
            </div>
        <?php endif; ?>
    </div>
</main>


<link rel="stylesheet"  href="<?= htmlspecialchars(STATIC_URL); ?>css/admin-support.css" ></link>
<script src="<?= htmlspecialchars(STATIC_URL); ?>js/admin-support.js" defer></script>

<?php require_once COMPONENT_PATH . "foot.php"; ?>