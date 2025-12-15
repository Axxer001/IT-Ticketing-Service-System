<?php
session_start();
require_once "../includes/sidebar_component.php";
require_once "../../classes/Database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->connect();

// Get all departments for filter
$deptQuery = "SELECT id, name FROM departments ORDER BY name";
$deptStmt = $db->prepare($deptQuery);
$deptStmt->execute();
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all service providers for filter
$providerQuery = "SELECT id, provider_name FROM service_providers ORDER BY provider_name";
$providerStmt = $db->prepare($providerQuery);
$providerStmt->execute();
$providers = $providerStmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];
$searchPerformed = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $searchPerformed = true;
    
    $query = "SELECT t.*, 
              e.first_name, e.last_name, 
              d.name as department_name,
              sp.provider_name,
              dt.type_name
              FROM tickets t
              JOIN employees e ON t.employee_id = e.id
              JOIN departments d ON t.department_id = d.id
              LEFT JOIN service_providers sp ON t.assigned_provider_id = sp.id
              JOIN device_types dt ON t.device_type_id = dt.id
              WHERE 1=1";
    
    $params = [];
    
    // Keyword search
    if (!empty($_GET['keyword'])) {
        $query .= " AND (t.ticket_number LIKE :keyword1 
                    OR t.issue_description LIKE :keyword2 
                    OR t.device_name LIKE :keyword3)";
        $searchTerm = '%' . $_GET['keyword'] . '%';
        $params['keyword1'] = $searchTerm;
        $params['keyword2'] = $searchTerm;
        $params['keyword3'] = $searchTerm;
    }
    
    // Status filter
    if (!empty($_GET['status'])) {
        $query .= " AND t.status = :status";
        $params['status'] = $_GET['status'];
    }
    
    // Priority filter
    if (!empty($_GET['priority'])) {
        $query .= " AND t.priority = :priority";
        $params['priority'] = $_GET['priority'];
    }
    
    // Department filter
    if (!empty($_GET['department'])) {
        $query .= " AND t.department_id = :department";
        $params['department'] = $_GET['department'];
    }
    
    // Provider filter
    if (!empty($_GET['provider'])) {
        $query .= " AND t.assigned_provider_id = :provider";
        $params['provider'] = $_GET['provider'];
    }
    
    // Date range
    if (!empty($_GET['date_from'])) {
        $query .= " AND DATE(t.created_at) >= :date_from";
        $params['date_from'] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $query .= " AND DATE(t.created_at) <= :date_to";
        $params['date_to'] = $_GET['date_to'];
    }
    
    $query .= " ORDER BY t.created_at DESC LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Advanced Search - Nexon</title>
<style>
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
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
    max-width: 1400px;
    margin: 24px auto;
    padding: 0 24px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 24px;
}

.search-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    margin-bottom: 24px;
}

.search-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

label {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--text-secondary);
}

input, select {
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-main);
    color: var(--text-primary);
    font-size: 14px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
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

.results-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead tr {
    border-bottom: 2px solid var(--border-color);
}

th {
    text-align: left;
    padding: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 13px;
}

td {
    padding: 16px 12px;
    border-bottom: 1px solid var(--border-color);
}

tbody tr:hover {
    background: var(--bg-main);
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge-pending { background: rgba(102, 126, 234, 0.15); color: #667eea; }
.badge-assigned { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-in_progress { background: rgba(6, 182, 212, 0.15); color: #06b6d4; }
.badge-resolved { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-low { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-high { background: rgba(251, 146, 60, 0.15); color: #fb923c; }
.badge-critical { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-secondary);
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
}
</style>
</head>
<body>



<div class="main-content">
    <div class="container">
    <h1 class="page-title">🔍 Advanced Ticket Search</h1>
    
    <div class="search-card">
        <form method="GET">
            <div class="search-grid">
                <div class="form-group">
                    <label>Keyword</label>
                    <input type="text" name="keyword" placeholder="Ticket #, description..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="assigned" <?= ($_GET['status'] ?? '') === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                        <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="resolved" <?= ($_GET['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="closed" <?= ($_GET['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority">
                        <option value="">All Priorities</option>
                        <option value="low" <?= ($_GET['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                        <option value="medium" <?= ($_GET['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="high" <?= ($_GET['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="critical" <?= ($_GET['priority'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Department</label>
                    <select name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($_GET['department'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Service Provider</label>
                    <select name="provider">
                        <option value="">All Providers</option>
                        <?php foreach ($providers as $provider): ?>
                        <option value="<?= $provider['id'] ?>" <?= ($_GET['provider'] ?? '') == $provider['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($provider['provider_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                </div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" name="search" class="btn btn-primary">🔍 Search</button>
                <a href="advanced.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>
    
    <?php if ($searchPerformed): ?>
    <div class="results-card">
        <div class="results-header">
            <h2 style="font-size: 20px; font-weight: 700;">Search Results</h2>
            <span style="color: var(--text-secondary);"><?= count($results) ?> tickets found</span>
        </div>
        
        <?php if (count($results) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Description</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Provider</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $ticket): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                    <td><?= htmlspecialchars(substr($ticket['issue_description'], 0, 40)) ?>...</td>
                    <td><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></td>
                    <td><?= htmlspecialchars($ticket['department_name']) ?></td>
                    <td><span class="badge badge-<?= $ticket['priority'] ?>"><?= strtoupper($ticket['priority']) ?></span></td>
                    <td><span class="badge badge-<?= $ticket['status'] ?>"><?= strtoupper(str_replace('_', ' ', $ticket['status'])) ?></span></td>
                    <td><?= htmlspecialchars($ticket['provider_name'] ?? 'Unassigned') ?></td>
                    <td><?= date('M d, Y', strtotime($ticket['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <h3>No tickets found</h3>
            <p>Try adjusting your search filters</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    </div>
</div>

<script>
    const PHP_SESSION_THEME = <?= json_encode($_SESSION['theme'] ?? 'light') ?>;
</script>
<script src="../../assets/js/theme.js?v=2"></script>
<script src="../../assets/js/notifications.js?v=2"></script>
</body>
</html>