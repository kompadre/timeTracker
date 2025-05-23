<?php
// Handles time tracking functionality (Clock In / Clock Out)
session_start();
require_once __DIR__ . '/includes/db.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// POST Request Check
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === "clock_in") {
        // Optional: Prevent double clock-in server-side
        $stmt_check = $pdo->prepare("SELECT id FROM work_hours WHERE user_id = ? AND end_time IS NULL");
        $stmt_check->execute([$user_id]);
        if ($stmt_check->fetch()) {
            // Already clocked in, redirect or show error (index.php should prevent this state)
            header("Location: index.php?error=already_clocked_in"); // Example error feedback
            exit;
        }

        $task_description = trim($_POST['task_description'] ?? '');
        $task_description = $task_description === '' ? null : $task_description;

        $stmt = $pdo->prepare("INSERT INTO work_hours (user_id, start_time, task_description) VALUES (?, NOW(), ?)");
        $stmt->execute([$user_id, $task_description]);

    } elseif ($action === "clock_out") {
        $work_entry_id = $_POST['work_entry_id'] ?? null;

        if (!empty($work_entry_id) && is_numeric($work_entry_id)) {
            $stmt = $pdo->prepare("UPDATE work_hours SET end_time = NOW() WHERE id = ? AND user_id = ? AND end_time IS NULL");
            $stmt->execute([$work_entry_id, $user_id]);
        } else {
            // Invalid work_entry_id, redirect with an error or handle
            header("Location: index.php?error=invalid_entry_id"); // Example error feedback
            exit;
        }
    }
} catch (PDOException $e) {
    error_log("Tracking Error: " . $e->getMessage());
    // Redirect with a generic error or handle more gracefully
    header("Location: index.php?error=db_error"); // Example error feedback
    exit;
}

header("Location: index.php");
exit;
?>
