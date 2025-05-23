<?php
// Utility functions

// Ensure create_test_user function is here if it was defined before
if (function_exists('create_test_user') === false) {
    function create_test_user(PDO $pdo, string $username, string $password): void {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$username, $password_hash]);
            echo "Test user '$username' created successfully.<br>";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Error code for duplicate entry
                echo "Test user '$username' already exists.<br>";
            } else {
                echo "Error creating test user '$username': " . $e->getMessage() . "<br>";
            }
        }
    }
}

function calculate_interval_formatted(string $startTime, ?string $endTime): string {
    if ($endTime === null) {
        return "Ongoing";
    }

    try {
        $start = new DateTime($startTime);
        $end = new DateTime($endTime);
        $interval = $start->diff($end);
        
        $parts = [];
        // Calculate total hours including days
        $hours = $interval->days * 24 + $interval->h;
        if ($hours > 0) {
            $parts[] = $hours . "h";
        }
        
        if ($interval->i > 0) {
            $parts[] = $interval->i . "m";
        }
        
        if ($interval->s > 0 || empty($parts)) { // Show seconds if duration is less than a minute or for zero duration
            $parts[] = $interval->s . "s";
        }
        return empty($parts) ? "0s" : implode(' ', $parts);
    } catch (Exception $e) {
        error_log("Error calculating interval: " . $e->getMessage());
        return "Error";
    }
}
?>
