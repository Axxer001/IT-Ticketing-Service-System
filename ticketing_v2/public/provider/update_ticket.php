<?php
session_start();
require_once "../../classes/Ticket.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'service_provider') {
    header("Location: ../login.php");
    exit;
}

$ticketObj = new Ticket();
$ticketId = $_GET['id'] ?? 0;
$ticket = $ticketObj->getById($ticketId);

if (!$ticket || $ticket['provider_user_id'] != $_SESSION['user_id']) {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $comment = $_POST['comment'] ?? null;
    
    $result = $ticketObj->updateStatus($ticketId, $status, $_SESSION['user_id'], $comment);
    
    if ($result['success']) {
        header("Location: ../tickets/view.php?id=$ticketId&updated=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Ticket - Nexon</title>
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

.form-group {
    margin-bottom: 24px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

select, textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    background: var(--bg-card);
    color: var(--text-primary);
}

textarea {
    min-height: 100px;
    resize: vertical;
}

select:focus, textarea:focus {
    outline: none;
    border-color: var(--primary);
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
</style>
</head>
<body>

<div class="card">
    <h1 class="page-title">Update Ticket Status</h1>
    <p class="page-subtitle">Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></p>

    <div class="ticket-info">
        <div class="info-row">
            <span class="info-label">Current Status:</span>
            <span style="text-transform:capitalize"><?= str_replace('_', ' ', $ticket['status']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Employee:</span>
            <span><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Issue:</span>
            <span><?= htmlspecialchars(substr($ticket['issue_description'], 0, 50)) ?>...</span>
        </div>
    </div>

    <form method="POST">
        <div class="form-group">
            <label>New Status</label>
            <select name="status" required>
                <?php if ($ticket['status'] === 'assigned'): ?>
                    <option value="in_progress">Start Working (In Progress)</option>
                    <option value="resolved">Mark as Resolved</option>
                <?php elseif ($ticket['status'] === 'in_progress'): ?>
                    <option value="in_progress" selected>In Progress</option>
                    <option value="resolved">Mark as Resolved</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Comment (Optional)</label>
            <textarea name="comment" placeholder="Add a comment about this update..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Ticket</button>
        <a href="../tickets/view.php?id=<?= $ticketId ?>" class="btn btn-secondary" style="display:block; text-align:center; text-decoration:none">Cancel</a>
    </form>
</div>
<script src="../../assets/js/theme.js"></script>
<script src="../../assets/js/notifications.js"></script>
</body>
</html>