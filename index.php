<?php
// Main application entry point
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php'; // For calculate_interval_formatted

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$current_status = "Clocked Out";
$current_work_entry_id = null;
$current_start_time_display = null; 

try {
    $stmt_current = $pdo->prepare("SELECT id, start_time, end_time FROM work_hours WHERE user_id = ? ORDER BY start_time DESC LIMIT 1");
    $stmt_current->execute([$user_id]);
    $latest_entry = $stmt_current->fetch();

    if ($latest_entry && $latest_entry['end_time'] === null) {
        $current_status = "Clocked In";
        $current_work_entry_id = $latest_entry['id'];
        $current_start_time_display = $latest_entry['start_time'];
    }
} catch (PDOException $e) {
    error_log("Error fetching work status: " . $e->getMessage());
    die("An error occurred while fetching your work status.");
}

$todays_entries = [];
$total_today_seconds = 0;
try {
    $stmt_today = $pdo->prepare("SELECT start_time, end_time FROM work_hours WHERE user_id = ? AND DATE(start_time) = CURDATE() ORDER BY start_time ASC");
    $stmt_today->execute([$user_id]);
    $todays_entries = $stmt_today->fetchAll();

    foreach ($todays_entries as $entry) {
        if ($entry['end_time']) {
            $start = new DateTime($entry['start_time']);
            $end = new DateTime($entry['end_time']);
            $diff = $end->getTimestamp() - $start->getTimestamp();
            $total_today_seconds += $diff;
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching today's entries: " . $e->getMessage());
}

$history_entries = [];
try {
    $stmt_history = $pdo->prepare("SELECT start_time, end_time FROM work_hours WHERE user_id = ? ORDER BY start_time DESC LIMIT 30");
    $stmt_history->execute([$user_id]);
    $history_entries = $stmt_history->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching history entries: " . $e->getMessage());
}

function format_seconds_to_hms(int $seconds): string {
    if ($seconds < 0) return "0s";
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    $parts = [];
    if ($h > 0) $parts[] = $h . "h";
    if ($m > 0) $parts[] = $m . "m";
    if ($s > 0 || empty($parts)) $parts[] = $s . "s";
    return empty($parts) ? "0s" : implode(' ', $parts);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Hours Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <a href="logout.php">Logout</a>
        </div>

        <h2>Time Tracking</h2>
        <p>Current Status: 
            <span class="<?php echo ($current_status === 'Clocked In') ? 'status-clocked-in' : 'status-clocked-out'; ?>">
                <?php echo htmlspecialchars($current_status); ?>
            </span>
        </p>

        <?php if ($current_status === 'Clocked In' && $current_start_time_display): ?>
            <p class="info">Clocked in since: <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($current_start_time_display))); ?></p>
        <?php endif; ?>

        <form class="time-tracking-form" action="track.php" method="post">
            <?php if ($current_status === "Clocked Out"): ?>
                <button type="submit" name="action" value="clock_in">Clock In</button>
            <?php else: // Clocked In ?>
                <input type="hidden" name="work_entry_id" value="<?php echo htmlspecialchars($current_work_entry_id); ?>">
                <button type="submit" name="action" value="clock_out">Clock Out</button>
            <?php endif; ?>
        </form>

        <div id="todays-summary">
            <h3>Today's Summary</h3>
            <?php if (empty($todays_entries)): ?>
                <p>No entries for today yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($todays_entries as $entry): ?>
                        <li>
                            <?php echo htmlspecialchars(date('H:i:s', strtotime($entry['start_time']))); ?> - 
                            <?php echo $entry['end_time'] ? htmlspecialchars(date('H:i:s', strtotime($entry['end_time']))) : 'Ongoing'; ?>
                            (<?php echo calculate_interval_formatted($entry['start_time'], $entry['end_time']); ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Total today: <?php echo format_seconds_to_hms($total_today_seconds); ?></strong></p>
            <?php endif; ?>
        </div>

        <div id="work-history">
            <h3>Work History (Last 30 entries)</h3>
            <?php if (empty($history_entries)): ?>
                <p>No work history found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history_entries as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($entry['start_time']))); ?></td>
                                <td><?php echo htmlspecialchars(date('H:i:s', strtotime($entry['start_time']))); ?></td>
                                <td><?php echo $entry['end_time'] ? htmlspecialchars(date('H:i:s', strtotime($entry['end_time']))) : 'N/A'; ?></td>
                                <td><?php echo calculate_interval_formatted($entry['start_time'], $entry['end_time']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
