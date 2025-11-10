<?php
session_start();
require_once "../../classes/User.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userType = $_SESSION['user_type'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports & Printables - Nexon</title>
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

.page-header {
    margin-bottom: 32px;
    text-align: center;
}

.page-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--text-secondary);
    font-size: 16px;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
}

.report-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 32px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.report-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.15);
}

.report-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.report-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.report-description {
    font-size: 14px;
    color: var(--text-secondary);
    line-height: 1.5;
}

.info-box {
    background: rgba(102, 126, 234, 0.1);
    border-left: 4px solid var(--primary);
    border-radius: 8px;
    padding: 16px;
    margin-top: 32px;
}

@media (max-width: 768px) {
    .reports-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">NEXON</div>
    <a href="../dashboard.php" class="back-btn">← Dashboard</a>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">📊 Reports & Printables</h1>
        <p class="page-subtitle">Generate and print various ticket reports</p>
    </div>

    <div class="reports-grid">
        <!-- Ticket Report (All Users) -->
        <a href="ticket_report.php" class="report-card">
            <div class="report-icon">🎫</div>
            <h3 class="report-title">Ticket Report</h3>
            <p class="report-description">
                Generate detailed report for any ticket including timeline, attachments, and comments
            </p>
        </a>

        <?php if ($userType === 'employee'): ?>
        <!-- My Tickets Report (Employee Only) -->
        <a href="my_tickets_report.php" class="report-card">
            <div class="report-icon">📝</div>
            <h3 class="report-title">My Tickets Report</h3>
            <p class="report-description">
                View and print a comprehensive report of all your submitted tickets
            </p>
        </a>
        <?php endif; ?>

        <?php if ($userType === 'admin' || $userType === 'service_provider'): ?>
        <!-- Service Provider Logs -->
        <a href="service_logs.php" class="report-card">
            <div class="report-icon">📋</div>
            <h3 class="report-title">Service Provider Logs</h3>
            <p class="report-description">
                View service provider activity logs and performance metrics for a specific period
            </p>
        </a>
        <?php endif; ?>

        <?php if ($userType === 'admin'): ?>
        <!-- Summary Report (Admin Only) -->
        <a href="summary_report.php" class="report-card">
            <div class="report-icon">📊</div>
            <h3 class="report-title">Summary Report</h3>
            <p class="report-description">
                Comprehensive system-wide report with statistics, trends, and provider performance
            </p>
        </a>

        <!-- Excel Export (Admin Only) -->
        <a href="export_excel.php" class="report-card">
            <div class="report-icon">📑</div>
            <h3 class="report-title">Excel Export</h3>
            <p class="report-description">
                Export ticket data to Excel spreadsheet for further analysis
            </p>
        </a>
        <?php endif; ?>
    </div>

    <div class="info-box">
        <strong>💡 Tip:</strong> All reports can be printed directly from your browser. 
        Use the print button (🖨️) available on each report page for best results.
    </div>
</div>

<script src="../../assets/js/theme.js"></script>
</body>
</html>