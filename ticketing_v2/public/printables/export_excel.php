<?php
session_start();
require_once "../../classes/Printables.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$printablesObj = new Printables();

// Check if export is requested
if (isset($_GET['export']) && $_GET['export'] === 'yes') {
    $filters = [
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'status' => $_GET['status'] ?? null,
        'priority' => $_GET['priority'] ?? null
    ];
    
    $tickets = $printablesObj->getTicketsForExport($filters);
    
    // Generate CSV (Excel-compatible)
    $filename = 'tickets_export_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV Header
    fputcsv($output, [
        'Ticket Number',
        'Status',
        'Priority',
        'Created Date',
        'Assigned Date',
        'Resolved Date',
        'Employee Name',
        'Employee Contact',
        'Department',
        'Device Type',
        'Device Name',
        'Issue Description',
        'Service Provider',
        'Rating'
    ]);
    
    // CSV Data
    foreach ($tickets as $ticket) {
        fputcsv($output, [
            $ticket['ticket_number'],
            ucfirst(str_replace('_', ' ', $ticket['status'])),
            ucfirst($ticket['priority']),
            date('Y-m-d H:i:s', strtotime($ticket['created_at'])),
            $ticket['assigned_at'] ? date('Y-m-d H:i:s', strtotime($ticket['assigned_at'])) : 'N/A',
            $ticket['resolved_at'] ? date('Y-m-d H:i:s', strtotime($ticket['resolved_at'])) : 'N/A',
            $ticket['employee_name'],
            $ticket['employee_contact'] ?? 'N/A',
            $ticket['department'],
            $ticket['device_type'],
            $ticket['device_name'],
            $ticket['issue_description'],
            $ticket['provider_name'] ?? 'Unassigned',
            $ticket['rating'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    exit;
}

// Otherwise, show the export form
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Excel Export - Nexon</title>
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

.container {
    max-width: 600px;
    width: 100%;
}

.navbar {
    background: var(--bg-card);
    border-bottom: 1px solid var(--border-color);
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
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

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 40px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    margin-top: 80px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
    text-align: center;
}

.page-subtitle {
    color: var(--text-secondary);
    text-align: center;
    margin-bottom: 32px;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

input, select {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    background: var(--bg-card);
    color: var(--text-primary);
    transition: border-color 0.3s;
}

input:focus, select:focus {
    outline: none;
    border-color: var(--primary);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.btn {
    width: 100%;
    padding: 14px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 16px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    transition: transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn:hover {
    transform: translateY(-2px);
}

.info-box {
    background: rgba(102, 126, 234, 0.1);
    border-left: 4px solid var(--primary);
    border-radius: 8px;
    padding: 16px;
    margin-top: 24px;
    font-size: 13px;
    line-height: 1.6;
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">NEXON</div>
    <a href="index.php" class="back-btn">← Back</a>
</nav>

<div class="container">
    <div class="card">
        <h1 class="page-title">📑 Excel Export</h1>
        <p class="page-subtitle">Export ticket data to Excel spreadsheet</p>

        <form method="GET">
            <input type="hidden" name="export" value="yes">
            
            <div class="form-row">
                <div class="form-group">
                    <label>From Date (Optional)</label>
                    <input type="date" name="date_from" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                </div>
                <div class="form-group">
                    <label>To Date (Optional)</label>
                    <input type="date" name="date_to" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Status (Optional)</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority (Optional)</label>
                    <select name="priority">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn">
                📥 Download Excel File
            </button>

            <div class="info-box">
                <strong>📊 What's Included:</strong><br>
                • All ticket details (number, status, priority)<br>
                • Employee and department information<br>
                • Device details and issue descriptions<br>
                • Service provider assignments<br>
                • Timestamps and ratings<br>
                <br>
                The file will be downloaded as CSV format, compatible with Microsoft Excel and Google Sheets.
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/theme.js"></script>
</body>
</html>