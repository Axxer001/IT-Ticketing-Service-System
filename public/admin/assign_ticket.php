<?php
session_start();
require_once "../../classes/User.php";
require_once "../../classes/Ticket.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$ticketObj = new Ticket();
$userObj = new User();
$ticketId = $_GET['id'] ?? 0;
$ticket = $ticketObj->getById($ticketId);

if (!$ticket) {
    die("Ticket not found");
}

$providers = $userObj->getAvailableServiceProviders();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providerId = $_POST['provider_id'];
    $result = $ticketObj->assign($ticketId, $providerId, $_SESSION['user_id']);
    
    if ($result['success']) {
        header("Location: ../tickets/view.php?id=$ticketId&assigned=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Ticket - Nexon</title>
<link rel="stylesheet" href="../../assets/css/theme.css">
<script>
    const PHP_SESSION_THEME = <?= json_encode($_SESSION['theme'] ?? 'light') ?>;
</script>
<style>
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --bg-main: #f8fafc;
    --bg-card: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

[data-theme="dark"] {
    --bg-main: #0f172a;
    --bg-card: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --border-color: #334155;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-main);
    color: var(--text-primary);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 40px;
    max-width: 600px;
    width: 100%;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--text-secondary);
    margin-bottom: 32px;
}

.ticket-info {
    background: var(--bg-main);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.info-label {
    font-weight: 600;
    color: var(--text-secondary);
}

.provider-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
}

.provider-item {
    display: flex;
    align-items: center;
    padding: 16px;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
}

.provider-item:hover {
    border-color: var(--primary);
    background: rgba(102, 126, 234, 0.05);
}

.provider-item input[type="radio"] {
    margin-right: 16px;
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.provider-info {
    flex: 1;
}

.provider-name {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 4px;
}

.provider-meta {
    font-size: 13px;
    color: var(--text-secondary);
}

.btn {
    width: 100%;
    padding: 14px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 16px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
}

.btn-secondary {
    background: var(--bg-main);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    margin-top: 12px;
}

.empty-state {
    text-align: center;
    padding: 32px;
    color: var(--text-secondary);
}
</style>
</head>
<body>

<div class="card">
    <h1 class="page-title">Assign Ticket</h1>
    <p class="page-subtitle">Select a service provider for ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></p>

    <div class="ticket-info">
        <div class="info-row">
            <span class="info-label">Employee:</span>
            <span><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Device:</span>
            <span><?= htmlspecialchars($ticket['device_type_name']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Priority:</span>
            <span style="text-transform:capitalize"><?= htmlspecialchars($ticket['priority']) ?></span>
        </div>
    </div>

    <?php if (empty($providers)): ?>
        <div class="empty-state">
            <p>No available service providers at the moment.</p>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="provider-list">
                <?php foreach ($providers as $provider): ?>
                    <label class="provider-item">
                        <input type="radio" name="provider_id" value="<?= $provider['id'] ?>" required>
                        <div class="provider-info">
                            <div class="provider-name"><?= htmlspecialchars($provider['provider_name']) ?></div>
                            <div class="provider-meta">
                                <?= htmlspecialchars($provider['specialization'] ?? 'General') ?> •
                                <?= $provider['current_assignments'] ?> active tickets •
                                <?= number_format($provider['rating_average'], 1) ?>⭐ (<?= $provider['total_ratings'] ?> ratings)
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary">Assign Ticket</button>
            <a href="../tickets/view.php?id=<?= $ticketId ?>" class="btn btn-secondary" style="display:block; text-align:center; text-decoration:none">Cancel</a>
        </form>
    <?php endif; ?>
</div>
<script src="../../assets/js/theme.js"></script>
<script src="../../assets/js/notifications.js"></script>
</body>
</html>