<?php
session_start();
require_once "../../classes/User.php";
require_once "../../classes/Ticket.php";
require_once "../../classes/Printables.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ticketObj = new Ticket();
$printablesObj = new Printables();
$userObj = new User();
$userType = $_SESSION['user_type'];

// Get user's tickets for dropdown
$filters = [];
if ($userType === 'employee') {
    $profile = $userObj->getUserProfile($_SESSION['user_id']);
    $filters['employee_id'] = $profile['profile']['id'];
} elseif ($userType === 'service_provider') {
    $profile = $userObj->getUserProfile($_SESSION['user_id']);
    $filters['provider_id'] = $profile['profile']['id'];
}

$tickets = $ticketObj->getTickets($filters, 100, 0);

$ticketData = null;
$selectedId = $_GET['ticket_id'] ?? null;

if ($selectedId) {
    $ticketData = $printablesObj->getTicketReport($selectedId);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket Report - Nexon</title>
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

@media print {
    .no-print { display: none !important; }
    body { background: white; color: black; }
    .report-content { box-shadow: none; border: 1px solid #ddd; }
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-main);
    color: var(--text-primary);
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

.navbar-actions {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.btn-secondary {
    background: var(--bg-main);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.container {
    max-width: 1000px;
    margin: 24px auto;
    padding: 0 24px;
}

.selector-card {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.form-group {
    margin-bottom: 16px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

select {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    background: var(--bg-card);
    color: var(--text-primary);
}

.report-content {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: var(--shadow);
    color: #333;
}

.report-header {
    text-align: center;
    border-bottom: 3px solid #667eea;
    padding-bottom: 24px;
    margin-bottom: 32px;
}

.company-name {
    font-size: 28px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 8px;
}

.report-title {
    font-size: 20px;
    color: #333;
    margin-bottom: 4px;
}

.ticket-number {
    font-size: 16px;
    color: #666;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 32px;
}

.info-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.section-title {
    font-size: 14px;
    font-weight: 700;
    color: #667eea;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 16px;
}

.info-row {
    display: flex;
    padding: 8px 0;
    border-bottom: 1px solid #e0e0e0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    flex: 0 0 140px;
    font-weight: 600;
    color: #666;
    font-size: 13px;
}

.info-value {
    flex: 1;
    color: #333;
    font-size: 13px;
}

.content-section {
    margin-bottom: 32px;
}

.issue-box {
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    border-radius: 4px;
    padding: 16px;
    line-height: 1.6;
    color: #333;
}

.timeline {
    position: relative;
    padding-left: 24px;
    border-left: 2px solid #e0e0e0;
}

.timeline-item {
    margin-bottom: 24px;
    position: relative;
}

.timeline-dot {
    position: absolute;
    left: -29px;
    top: 4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #667eea;
}

.timeline-header {
    font-weight: 600;
    color: #333;
    font-size: 13px;
    margin-bottom: 4px;
}

.timeline-time {
    font-size: 11px;
    color: #999;
    margin-bottom: 8px;
}

.timeline-content {
    font-size: 13px;
    color: #666;
    line-height: 1.5;
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

.report-footer {
    margin-top: 48px;
    padding-top: 24px;
    border-top: 2px solid #e0e0e0;
    text-align: center;
    font-size: 11px;
    color: #999;
}

.empty-state {
    text-align: center;
    padding: 48px;
    color: #999;
}

@media print {
    @page { margin: 20mm; }
    .report-content { padding: 20px; }
}
</style>
</head>
<body>

<nav class="navbar no-print">
    <div class="navbar-brand">NEXON</div>
    <div class="navbar-actions">
        <?php if ($ticketData): ?>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">← Back</a>
    </div>
</nav>

<div class="container">
    <div class="selector-card no-print">
        <div class="form-group">
            <label>Select Ticket to Generate Report</label>
            <select onchange="window.location.href='ticket_report.php?ticket_id='+this.value">
                <option value="">-- Choose a Ticket --</option>
                <?php foreach ($tickets as $ticket): ?>
                    <option value="<?= $ticket['id'] ?>" <?= $selectedId == $ticket['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ticket['ticket_number']) ?> - <?= htmlspecialchars($ticket['device_type_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($ticketData): ?>
    <div class="report-content">
        <div class="report-header">
            <div class="company-name">NEXON IT TICKETING SYSTEM</div>
            <div class="report-title">Ticket Report</div>
            <div class="ticket-number">Ticket #<?= htmlspecialchars($ticketData['ticket_number']) ?></div>
        </div>

        <div class="info-grid">
            <div class="info-section">
                <div class="section-title">Ticket Information</div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="badge badge-<?= str_replace('_', '-', $ticketData['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', $ticketData['status'])) ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Priority:</span>
                    <span class="info-value" style="text-transform:capitalize"><strong><?= $ticketData['priority'] ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Device Type:</span>
                    <span class="info-value"><?= htmlspecialchars($ticketData['device_type_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Device Name:</span>
                    <span class="info-value"><?= htmlspecialchars($ticketData['device_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span class="info-value"><?= date('M j, Y g:i A', strtotime($ticketData['created_at'])) ?></span>
                </div>
                <?php if ($ticketData['resolved_at']): ?>
                <div class="info-row">
                    <span class="info-label">Resolved:</span>
                    <span class="info-value"><?= date('M j, Y g:i A', strtotime($ticketData['resolved_at'])) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <div class="section-title">Contact Information</div>
                <div class="info-row">
                    <span class="info-label">Employee:</span>
                    <span class="info-value"><?= htmlspecialchars($ticketData['first_name'] . ' ' . $ticketData['last_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($ticketData['employee_email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?= htmlspecialchars($ticketData['contact_number'] ?? 'N/A') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department:</span>
                    <span class="info-value"><?= htmlspecialchars($ticketData['department_name']) ?></span>
                </div>
                <?php if ($ticketData['provider_name']): ?>
                <div class="info-row">
                    <span class="info-label">Service Provider:</span>
                    <span class="info-value"><?= htmlspecialchars($ticketData['provider_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">Issue Description</div>
            <div class="issue-box">
                <?= nl2br(htmlspecialchars($ticketData['issue_description'])) ?>
            </div>
        </div>

        <?php if (!empty($ticketData['attachments'])): ?>
        <div class="content-section">
            <div class="section-title">Attachments (<?= count($ticketData['attachments']) ?>)</div>
            <?php foreach ($ticketData['attachments'] as $att): ?>
                <div style="padding:8px 0; border-bottom:1px solid #e0e0e0; font-size:13px">
                    📎 <?= htmlspecialchars($att['file_name']) ?> (<?= number_format($att['file_size'] / 1024, 1) ?> KB)
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="content-section">
            <div class="section-title">Activity Timeline</div>
            <div class="timeline">
                <?php foreach ($ticketData['updates'] as $update): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-header"><?= htmlspecialchars($update['email']) ?></div>
                    <div class="timeline-time"><?= date('M j, Y g:i A', strtotime($update['created_at'])) ?></div>
                    <div class="timeline-content"><?= nl2br(htmlspecialchars($update['message'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($ticketData['rating']): ?>
        <div class="content-section">
            <div class="section-title">Service Rating</div>
            <div class="issue-box">
                <div style="font-size:24px; margin-bottom:8px">
                    <?= str_repeat('⭐', $ticketData['rating']) ?>
                </div>
                <?php if ($ticketData['rating_feedback']): ?>
                <div style="margin-top:12px; font-style:italic">
                    "<?= htmlspecialchars($ticketData['rating_feedback']) ?>"
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="report-footer">
            <p><strong>Nexon IT Ticketing System</strong></p>
            <p>Report Generated: <?= date('F j, Y g:i A') ?></p>
            <p>This is an official system-generated report</p>
        </div>
    </div>
    <?php else: ?>
    <div class="report-content">
        <div class="empty-state">
            <div style="font-size:64px; margin-bottom:16px">📋</div>
            <p>Please select a ticket to generate report</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="../../assets/js/theme.js"></script>
</body>
</html>