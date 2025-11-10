<?php
session_start();
require_once "../../classes/User.php";
require_once "../../classes/Ticket.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header("Location: ../login.php");
    exit;
}

$userObj = new User();
$ticketObj = new Ticket();
$profile = $userObj->getUserProfile($_SESSION['user_id']);
$deviceTypes = $ticketObj->getDeviceTypes();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'device_type_id' => $_POST['device_type_id'],
            'device_name' => $_POST['device_name'],
            'issue_description' => $_POST['issue_description'],
            'priority' => $_POST['priority']
        ];
        
        // Handle file uploads
        $attachments = [];
        if (isset($_FILES['attachments'])) {
            foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $attachments[] = [
                        'name' => $_FILES['attachments']['name'][$key],
                        'type' => $_FILES['attachments']['type'][$key],
                        'tmp_name' => $tmp_name,
                        'size' => $_FILES['attachments']['size'][$key]
                    ];
                }
            }
        }
        
        $result = $ticketObj->create($profile['profile']['id'], $data, $attachments);
        
        if ($result['success']) {
            header("Location: view.php?id=" . $result['ticket_id'] . "&created=1");
            exit;
        } else {
            $error = $result['message'];
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Ticket - Nexon</title>
<!-- Theme CSS -->
<link rel="stylesheet" href="../assets/css/theme.css">
<!-- OR adjust path based on file location -->
<link rel="stylesheet" href="../../assets/css/theme.css">
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
    max-width: 800px;
    margin: 24px auto;
    padding: 0 24px;
}

.page-header {
    margin-bottom: 24px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--text-secondary);
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 32px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-left: 4px solid #ef4444;
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

.required {
    color: #ef4444;
}

input, select, textarea {
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

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--primary);
}

textarea {
    min-height: 150px;
    resize: vertical;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.file-upload-area {
    border: 2px dashed var(--border-color);
    border-radius: 10px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.file-upload-area:hover {
    border-color: var(--primary);
    background: rgba(102, 126, 234, 0.05);
}

.file-upload-area input {
    display: none;
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 8px;
    opacity: 0.5;
}

.upload-text {
    color: var(--text-secondary);
    font-size: 14px;
}

.file-list {
    margin-top: 16px;
}

.file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: var(--bg-main);
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 13px;
}

.file-item button {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 4px 8px;
}

.btn {
    padding: 14px 24px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    width: 100%;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
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
    <a href="../dashboard.php" class="back-btn">← Dashboard</a>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create New Ticket</h1>
        <p class="page-subtitle">Submit a new IT support request</p>
    </div>

    <div class="card">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="ticketForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Device Type <span class="required">*</span></label>
                    <select name="device_type_id" required>
                        <option value="">Select Device Type</option>
                        <?php foreach ($deviceTypes as $type): ?>
                            <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Device Name/Model <span class="required">*</span></label>
                    <input type="text" name="device_name" placeholder="e.g., HP Laptop 15-dw1xxx" required>
                </div>
            </div>

            <div class="form-group">
                <label>Priority Level <span class="required">*</span></label>
                <select name="priority" required>
                    <option value="low">Low - Can wait a few days</option>
                    <option value="medium" selected>Medium - Normal request</option>
                    <option value="high">High - Urgent issue</option>
                    <option value="critical">Critical - System down</option>
                </select>
            </div>

            <div class="form-group">
                <label>Issue Description <span class="required">*</span></label>
                <textarea name="issue_description" placeholder="Please describe the issue in detail..." required></textarea>
            </div>

            <div class="form-group">
                <label>Attachments (Optional)</label>
                <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                    <div class="upload-icon">📎</div>
                    <div class="upload-text">Click to upload images or documents</div>
                    <div class="upload-text" style="font-size:12px; margin-top:4px">Max 5 files, 10MB each</div>
                    <input type="file" id="fileInput" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx">
                </div>
                <div class="file-list" id="fileList"></div>
            </div>

            <button type="submit" class="btn btn-primary">Submit Ticket</button>
        </form>
    </div>
</div>

<script>
const fileInput = document.getElementById('fileInput');
const fileList = document.getElementById('fileList');
let selectedFiles = [];

fileInput.addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    
    if (selectedFiles.length + files.length > 5) {
        alert('Maximum 5 files allowed');
        return;
    }
    
    files.forEach(file => {
        if (file.size > 10485760) {
            alert(`File ${file.name} is too large. Maximum size is 10MB`);
            return;
        }
        selectedFiles.push(file);
    });
    
    displayFiles();
});

function displayFiles() {
    fileList.innerHTML = '';
    selectedFiles.forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.innerHTML = `
            <span>📄 ${file.name} (${(file.size / 1024).toFixed(1)} KB)</span>
            <button type="button" onclick="removeFile(${index})">✕</button>
        `;
        fileList.appendChild(fileItem);
    });
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    displayFiles();
    
    // Update file input
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    fileInput.files = dt.files;
}
</script>
<!-- Theme Switcher -->
<script src="../assets/js/theme.js"></script>

<!-- Notifications (only on authenticated pages) -->
<script src="../assets/js/notifications.js"></script>
</body>
</html>