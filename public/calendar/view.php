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

// Get current month/year or from query params
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get tickets for the month
$startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$endDate = date('Y-m-t', strtotime($startDate));

$query = "SELECT t.*, e.first_name, e.last_name, DATE(t.created_at) as ticket_date
          FROM tickets t
          JOIN employees e ON t.employee_id = e.id
          WHERE DATE(t.created_at) BETWEEN :start AND :end
          ORDER BY t.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group tickets by date
$ticketsByDate = [];
foreach ($tickets as $ticket) {
    $date = $ticket['ticket_date'];
    if (!isset($ticketsByDate[$date])) {
        $ticketsByDate[$date] = [];
    }
    $ticketsByDate[$date][] = $ticket;
}

// Calendar calculations
$firstDay = date('N', strtotime($startDate)); // 1 (Monday) to 7 (Sunday)
$totalDays = date('t', strtotime($startDate));

// Navigation
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calendar View - Nexon</title>
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

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.month-nav {
    display: flex;
    gap: 12px;
    align-items: center;
}

.nav-btn {
    padding: 8px 16px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-primary);
    font-weight: 600;
}

.current-month {
    font-size: 24px;
    font-weight: 700;
}

.calendar-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--border-color);
    border: 1px solid var(--border-color);
}

.calendar-day-header {
    background: var(--bg-card);
    padding: 12px;
    text-align: center;
    font-weight: 600;
    font-size: 13px;
    color: var(--text-secondary);
}

.calendar-day {
    background: var(--bg-card);
    min-height: 120px;
    padding: 8px;
    position: relative;
}

.calendar-day.empty {
    background: var(--bg-main);
}

.day-number {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
}

.day-today {
    background: rgba(102, 126, 234, 0.1);
}

.day-number.today {
    color: var(--primary);
    font-weight: 700;
}

.ticket-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 4px;
}

.ticket-item {
    font-size: 11px;
    padding: 4px 6px;
    margin-bottom: 2px;
    border-radius: 4px;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ticket-item.pending { background: rgba(102, 126, 234, 0.15); color: #667eea; }
.ticket-item.assigned { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.ticket-item.in_progress { background: rgba(6, 182, 212, 0.15); color: #06b6d4; }
.ticket-item.resolved { background: rgba(16, 185, 129, 0.15); color: #10b981; }

.legend {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}
</style>
</head>
<body>



<div class="main-content">
<div class="container">
    <div class="calendar-header">
        <h1 class="current-month">📅 <?= date('F Y', strtotime("$year-$month-01")) ?></h1>
        <div class="month-nav">
            <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="nav-btn">← Previous</a>
            <a href="?month=<?= date('m') ?>&year=<?= date('Y') ?>" class="nav-btn">Today</a>
            <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="nav-btn">Next →</a>
        </div>
    </div>
    
    <div class="calendar-card">
        <div class="calendar">
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
            <div class="calendar-day-header">Sun</div>
            
            <?php
            // Empty cells before first day
            for ($i = 1; $i < $firstDay; $i++) {
                echo '<div class="calendar-day empty"></div>';
            }
            
            // Calendar days
            for ($day = 1; $day <= $totalDays; $day++) {
                $currentDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                $isToday = $currentDate === date('Y-m-d');
                $dayTickets = $ticketsByDate[$currentDate] ?? [];
                
                echo '<div class="calendar-day ' . ($isToday ? 'day-today' : '') . '">';
                echo '<div class="day-number ' . ($isToday ? 'today' : '') . '">' . $day . '</div>';
                
                // Show first 3 tickets
                $displayTickets = array_slice($dayTickets, 0, 3);
                foreach ($displayTickets as $ticket) {
                    echo '<div class="ticket-item ' . $ticket['status'] . '" title="' . htmlspecialchars($ticket['ticket_number'] . ': ' . $ticket['issue_description']) . '">';
                    echo htmlspecialchars($ticket['ticket_number']);
                    echo '</div>';
                }
                
                // Show "more" indicator
                if (count($dayTickets) > 3) {
                    echo '<div style="font-size: 10px; color: var(--text-secondary); margin-top: 2px;">+' . (count($dayTickets) - 3) . ' more</div>';
                }
                
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="legend">
            <div class="legend-item">
                <div class="ticket-dot" style="background: #667eea;"></div>
                <span>Pending</span>
            </div>
            <div class="legend-item">
                <div class="ticket-dot" style="background: #f59e0b;"></div>
                <span>Assigned</span>
            </div>
            <div class="legend-item">
                <div class="ticket-dot" style="background: #06b6d4;"></div>
                <span>In Progress</span>
            </div>
            <div class="legend-item">
                <div class="ticket-dot" style="background: #10b981;"></div>
                <span>Resolved</span>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    const PHP_SESSION_THEME = <?= json_encode($_SESSION['theme'] ?? 'light') ?>;
</script>
<script src="../../assets/js/theme.js?v=2"></script>
<script src="../../assets/js/notifications.js?v=2"></script>
</body>
</html>