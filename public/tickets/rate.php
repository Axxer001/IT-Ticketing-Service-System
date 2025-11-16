<?php
session_start();
require_once "../../classes/User.php";
require_once "../../classes/Ticket.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header("Location: ../login.php");
    exit;
}

$ticketObj = new Ticket();
$userObj = new User();
$ticketId = $_GET['id'] ?? 0;
$ticket = $ticketObj->getById($ticketId);

if (!$ticket || $ticket['status'] !== 'resolved' || $ticket['rating']) {
    header("Location: view.php?id=$ticketId");
    exit;
}

$profile = $userObj->getUserProfile($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'];
    $feedback = $_POST['feedback'] ?? '';
    
    $result = $ticketObj->submitRating(
        $ticketId,
        $profile['profile']['id'],
        $ticket['assigned_provider_id'],
        $rating,
        $feedback
    );
    
    if ($result['success']) {
        header("Location: view.php?id=$ticketId&rated=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rate Service - Nexon</title>
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
    max-width: 500px;
    width: 100%;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
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
}

.rating-container {
    text-align: center;
    margin-bottom: 32px;
}

.stars {
    display: flex;
    justify-content: center;
    gap: 8px;
    font-size: 48px;
    cursor: pointer;
    user-select: none;
}

.star {
    transition: all 0.2s;
    opacity: 0.3;
}

.star.active,
.star:hover {
    opacity: 1;
    transform: scale(1.2);
}

.rating-label {
    margin-top: 16px;
    font-size: 18px;
    font-weight: 600;
    color: var(--primary);
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
}

.btn:hover {
    transform: translateY(-2px);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
</head>
<body>

<div class="card">
    <h1 class="page-title">Rate Service</h1>
    <p class="page-subtitle">How would you rate the service provided by <?= htmlspecialchars($ticket['provider_name']) ?>?</p>

    <form method="POST" id="ratingForm">
        <div class="rating-container">
            <div class="stars" id="stars">
                <span class="star" data-rating="1">⭐</span>
                <span class="star" data-rating="2">⭐</span>
                <span class="star" data-rating="3">⭐</span>
                <span class="star" data-rating="4">⭐</span>
                <span class="star" data-rating="5">⭐</span>
            </div>
            <div class="rating-label" id="ratingLabel">Select a rating</div>
            <input type="hidden" name="rating" id="ratingInput" required>
        </div>

        <div class="form-group">
            <label>Feedback (Optional)</label>
            <textarea name="feedback" placeholder="Tell us about your experience..."></textarea>
        </div>

        <button type="submit" class="btn" id="submitBtn" disabled>Submit Rating</button>
    </form>
</div>

<script>
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('ratingInput');
const ratingLabel = document.getElementById('ratingLabel');
const submitBtn = document.getElementById('submitBtn');

const labels = {
    1: 'Poor',
    2: 'Fair',
    3: 'Good',
    4: 'Very Good',
    5: 'Excellent'
};

stars.forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        ratingInput.value = rating;
        submitBtn.disabled = false;
        
        stars.forEach((s, index) => {
            if (index < rating) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
        
        ratingLabel.textContent = labels[rating];
    });
    
    star.addEventListener('mouseenter', function() {
        const rating = this.dataset.rating;
        stars.forEach((s, index) => {
            if (index < rating) {
                s.style.opacity = '1';
            }
        });
    });
    
    star.addEventListener('mouseleave', function() {
        const currentRating = ratingInput.value;
        stars.forEach((s, index) => {
            if (!currentRating || index >= currentRating) {
                s.style.opacity = s.classList.contains('active') ? '1' : '0.3';
            }
        });
    });
});
</script>
<script src="../../assets/js/theme.js"></script>
<script src="../../assets/js/notifications.js"></script>
</body>
</html>