<?php
session_start();
require_once "../../classes/BatchOperations.php";
require_once "../../classes/Database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}


$batchOps = new BatchOperations();
$database = new Database();
$db = $database->connect();

$message = '';
$messageType = '';

// Handle batch operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $ticketIds = isset($_POST['ticket_ids']) ? json_decode($_POST['ticket_ids'], true) : [];
    
    if (empty($ticketIds)) {
        $message = "Please select at least one ticket.";
        $messageType = "error";
    } else {
        switch ($_POST['action']) {
            case 'assign':
                $providerId = (int)$_POST['provider_id'];
                if ($batchOps->batchAssign($ticketIds, $providerId, $_SESSION['user_id'])) {
                    $message = count($ticketIds) . " tickets assigned successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to assign tickets.";
                    $messageType = "error";
                }
                break;
                
            case 'status':
                $newStatus = $_POST['new_status'];
                if ($batchOps->batchUpdateStatus($ticketIds, $newStatus, $_SESSION['user_id'])) {
                    $message = count($ticketIds) . " tickets updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to update tickets.";
                    $messageType = "error";
                }
                break;
                
            case 'priority':
                $newPriority = $_POST['new_priority'];
                if ($batchOps->batchUpdatePriority($ticketIds, $newPriority, $_SESSION['user_id'])) {
                    $message = count($ticketIds) . " tickets updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to update tickets.";
                    $messageType = "error";
                }
                break;
        }
    }
}

// Get all tickets for selection
$query = "SELECT t.*, e.first_name, e.last_name, d.name as department_name, sp.provider_name
          FROM tickets t
          JOIN employees e ON t.employee_id = e.id
          JOIN departments d ON t.department_id = d.id
          LEFT JOIN service_providers sp ON t.assigned_provider_id = sp.id
          WHERE t.status NOT IN ('closed', 'cancelled')
          ORDER BY t.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get service providers
$providerQuery = "SELECT id, provider_name FROM service_providers WHERE is_available = 1";
$providerStmt = $db->prepare($providerQuery);
$providerStmt->execute();
$providers = $providerStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Batch Operations - Nexon</title>
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

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border: 1px solid #10b981;
}

.alert-error {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid #ef4444;
}

.actions-bar {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.action-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

select, input {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-main);
    color: var(--text-primary);
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
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

.btn-success {
    background: var(--success);
    color: white;
}

.btn-warning {
    background: var(--warning);
    color: white;
}

.selected-count {
    padding: 8px 16px;
    background: rgba(102, 126, 234, 0.15);
    color: var(--primary);
    border-radius: 8px;
    font-weight: 600;
}

.table-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
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

input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
</style>
</head>
<body>
<?php require_once "../includes/sidebar_component.php"; ?>



<div class="main-content">
<div class="container">
    <h1 class="page-title">⚡ Batch Ticket Operations</h1>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <div class="actions-bar">
        <span class="selected-count">Selected: <span id="selectedCount">0</span></span>
        
        <div class="action-group">
            <select id="providerSelect">
                <option value="">Select Provider</option>
                <?php foreach ($providers as $provider): ?>
                <option value="<?= $provider['id'] ?>"><?= htmlspecialchars($provider['provider_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="batchAssign()" class="btn btn-primary">Assign</button>
        </div>
        
        <div class="action-group">
            <select id="statusSelect">
                <option value="">Select Status</option>
                <option value="pending">Pending</option>
                <option value="assigned">Assigned</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
            </select>
            <button onclick="batchStatus()" class="btn btn-success">Update Status</button>
        </div>
        
        <div class="action-group">
            <select id="prioritySelect">
                <option value="">Select Priority</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>
            <button onclick="batchPriority()" class="btn btn-warning">Update Priority</button>
        </div>
    </div>
    
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                    <th>Ticket #</th>
                    <th>Description</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Provider</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><input type="checkbox" class="ticket-checkbox" value="<?= $ticket['id'] ?>" onchange="updateCount()"></td>
                    <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                    <td><?= htmlspecialchars(substr($ticket['issue_description'], 0, 40)) ?>...</td>
                    <td><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></td>
                    <td><?= htmlspecialchars($ticket['department_name']) ?></td>
                    <td><span class="badge badge-<?= $ticket['priority'] ?>"><?= strtoupper($ticket['priority']) ?></span></td>
                    <td><span class="badge badge-<?= $ticket['status'] ?>"><?= strtoupper(str_replace('_', ' ', $ticket['status'])) ?></span></td>
                    <td><?= htmlspecialchars($ticket['provider_name'] ?? 'Unassigned') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<form id="batchForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="actionInput">
    <input type="hidden" name="ticket_ids" id="ticketIdsInput">
    <input type="hidden" name="provider_id" id="providerIdInput">
    <input type="hidden" name="new_status" id="newStatusInput">
    <input type="hidden" name="new_priority" id="newPriorityInput">
</form>

<script>
function getSelectedTickets() {
    const checkboxes = document.querySelectorAll('.ticket-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function updateCount() {
    const count = document.querySelectorAll('.ticket-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.ticket-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateCount();
}

function batchAssign() {
    const tickets = getSelectedTickets();
    const providerId = document.getElementById('providerSelect').value;
    
    if (tickets.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }
    
    if (!providerId) {
        alert('Please select a service provider.');
        return;
    }
    
    if (confirm(`Assign ${tickets.length} ticket(s) to the selected provider?`)) {
        document.getElementById('actionInput').value = 'assign';
        document.getElementById('ticketIdsInput').value = JSON.stringify(tickets);
        document.getElementById('providerIdInput').value = providerId;
        document.getElementById('batchForm').submit();
    }
}

function batchStatus() {
    const tickets = getSelectedTickets();
    const status = document.getElementById('statusSelect').value;
    
    if (tickets.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }
    
    if (!status) {
        alert('Please select a status.');
        return;
    }
    
    if (confirm(`Update status of ${tickets.length} ticket(s)?`)) {
        document.getElementById('actionInput').value = 'status';
        document.getElementById('ticketIdsInput').value = JSON.stringify(tickets);
        document.getElementById('newStatusInput').value = status;
        document.getElementById('batchForm').submit();
    }
}

function batchPriority() {
    const tickets = getSelectedTickets();
    const priority = document.getElementById('prioritySelect').value;
    
    if (tickets.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }
    
    if (!priority) {
        alert('Please select a priority.');
        return;
    }
    
    if (confirm(`Update priority of ${tickets.length} ticket(s)?`)) {
        document.getElementById('actionInput').value = 'priority';
        document.getElementById('ticketIdsInput').value = JSON.stringify(tickets);
        document.getElementById('newPriorityInput').value = priority;
        document.getElementById('batchForm').submit();
    }
}
</script>

<script>
    const PHP_SESSION_THEME = <?= json_encode($_SESSION['theme'] ?? 'light') ?>;
</script>
<script src="../../assets/js/theme.js?v=2"></script>
<script src="../../assets/js/notifications.js?v=2"></script>
</body>
</html>