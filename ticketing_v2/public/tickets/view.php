<?php
session_start();
require_once "../../classes/User.php";
require_once "../../classes/Ticket.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ticketObj = new Ticket();
$ticketId = $_GET['id'] ?? 0;
$ticket = $ticketObj->getById($ticketId);

if (!$ticket) {
    die("Ticket not found");
}

// Check access permissions
$userType = $_SESSION['user_type'];
$canView = false;

if ($userType === 'admin') {
    $canView = true;
} elseif ($userType === 'employee') {
    $canView = ($ticket['employee_user_id'] == $_SESSION['user_id']);
} elseif ($userType === 'service_provider') {
    $canView = ($ticket['provider_user_id'] == $_SESSION['user_id']);
}

if (!$canView) {
    die("Access denied");
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment = $_POST['comment'];
    $ticketObj->addComment($ticketId, $_SESSION['user_id'], $comment);
    header("Location: view.php?id=$ticketId");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?> - Nexon</title>
<link rel="stylesheet" href="../../assets/css/theme.css">
<script>
    const PHP_SESSION_THEME = <?= json_encode($_SESSION['theme'] ?? 'light') ?>;
</script>
<style>
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
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
}

.navbar {
    background: var(--bg-card);
    border-bottom: 1px solid var(--border-color);
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow);
}

.navbar-brand {
    font-size: 24px;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.back-btn {
    padding: 8px 16px;
    background: var(--bg-main);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-primary);
    font-weight: 600;
}

.container {
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 24px;
}

.ticket-header {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.ticket-number {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.ticket-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 16px;
}

.ticket-meta {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.meta-label {
    font-size: 12px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.meta-value {
    font-weight: 600;
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-assigned { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.badge-in-progress { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.badge-resolved { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-closed { background: rgba(100, 116, 139, 0.15); color: #64748b; }
.badge-low { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.badge-medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-high { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.badge-critical { background: rgba(220, 38, 38, 0.2); color: #dc2626; }

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    height: fit-content;
}

.card-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

.issue-description {
    line-height: 1.6;
    color: var(--text-primary);
    margin-bottom: 24px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: var(--text-secondary);
}

.timeline {
    position: relative;
}

.timeline-item {
    position: relative;
    padding-left: 32px;
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-left: 2px solid var(--border-color);
}

.timeline-item:last-child {
    border-left-color: transparent;
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: -6px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--primary);
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.timeline-user {
    font-weight: 600;
    font-size: 14px;
}

.timeline-time {
    font-size: 12px;
    color: var(--text-secondary);
}

.timeline-content {
    font-size: 14px;
    line-height: 1.5;
    color: var(--text-primary);
}

.comment-form {
    margin-top: 24px;
}

textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    min-height: 100px;
    background: var(--bg-card);
    color: var(--text-primary);
}

textarea:focus {
    outline: none;
    border-color: var(--primary);
}

.btn {
    padding: 10px 20px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    margin-top: 12px;
}

.btn-primary:hover {
    transform: translateY(-2px);
}

.attachments {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.attachment-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: var(--bg-main);
    border-radius: 8px;
    font-size: 13px;
}

@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">NEXON</div>
    <div style="display:flex; gap:12px;">
        <a href="../printables/index.php" class="back-btn">📊 Reports</a>
        <a href="../dashboard.php" class="back-btn">← Dashboard</a>
    </div>
</nav>

<div class="container">
    <div class="ticket-header">
        <div class="ticket-number">Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></div>
        <h1 class="ticket-title"><?= htmlspecialchars($ticket['device_type_name']) ?> - <?= htmlspecialchars($ticket['device_name']) ?></h1>
        
        <div class="ticket-meta">
            <div class="meta-item">
                <span class="meta-label">Status</span>
                <span class="badge badge-<?= str_replace('_', '-', $ticket['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                </span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Priority</span>
                <span class="badge badge-<?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Created</span>
                <span class="meta-value"><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></span>
            </div>
            <?php if ($ticket['resolved_at']): ?>
            <div class="meta-item">
                <span class="meta-label">Resolved</span>
                <span class="meta-value"><?= date('M j, Y g:i A', strtotime($ticket['resolved_at'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="content-grid">
        <div>
            <div class="card">
                <h2 class="card-title">Issue Description</h2>
                <div class="issue-description"><?= nl2br(htmlspecialchars($ticket['issue_description'])) ?></div>
                
                <?php if (!empty($ticket['attachments'])): ?>
                <h3 style="font-size:16px; margin-bottom:12px">Attachments</h3>
                <div class="attachments">
                    <?php foreach ($ticket['attachments'] as $att): ?>
                        <div class="attachment-item">
                            📎 <a href="../../<?= htmlspecialchars($att['file_path']) ?>" target="_blank"><?= htmlspecialchars($att['file_name']) ?></a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="card" style="margin-top:24px">
                <h2 class="card-title">Activity Timeline</h2>
                <div class="timeline">
                    <?php foreach ($ticket['updates'] as $update): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-header">
                            <span class="timeline-user"><?= htmlspecialchars($update['email']) ?></span>
                            <span class="timeline-time"><?= date('M j, g:i A', strtotime($update['created_at'])) ?></span>
                        </div>
                        <div class="timeline-content"><?= nl2br(htmlspecialchars($update['message'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="comment-form">
                    <form method="POST">
                        <textarea name="comment" placeholder="Add a comment..." required></textarea>
                        <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
                    </form>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <h2 class="card-title">Details</h2>
                <div class="info-row">
                    <span class="info-label">Employee</span>
                    <span><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department</span>
                    <span><?= htmlspecialchars($ticket['department_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact</span>
                    <span><?= htmlspecialchars($ticket['contact_number'] ?? 'N/A') ?></span>
                </div>
                <?php if ($ticket['assigned_provider_id']): ?>
                <div class="info-row">
                    <span class="info-label">Assigned To</span>
                    <span><?= htmlspecialchars($ticket['provider_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($userType === 'admin' && $ticket['status'] === 'pending'): ?>
            <div class="card" style="margin-top:16px">
                <a href="../admin/assign_ticket.php?id=<?= $ticket['id'] ?>" class="btn btn-primary" style="width:100%; text-align:center; display:block; text-decoration:none">
                    Assign Ticket
                </a>
            </div>
            <?php endif; ?>

            <?php if ($userType === 'service_provider' && in_array($ticket['status'], ['assigned', 'in_progress'])): ?>
            <div class="card" style="margin-top:16px">
                <a href="../provider/update_ticket.php?id=<?= $ticket['id'] ?>" class="btn btn-primary" style="width:100%; text-align:center; display:block; text-decoration:none">
                    Update Status
                </a>
            </div>
            <?php endif; ?>

            <?php if ($userType === 'employee' && $ticket['status'] === 'resolved' && !$ticket['rating']): ?>
            <div class="card" style="margin-top:16px">
                <a href="rate.php?id=<?= $ticket['id'] ?>" class="btn btn-primary" style="width:100%; text-align:center; display:block; text-decoration:none">
                    Rate Service
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="../../assets/js/theme.js"></script>
<script src="../../assets/js/notifications.js"></script>
</body>
</html>