<?php
require('../conn.php'); // Ensure this includes your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alert_id'])) {
    $alert_id = intval($_POST['alert_id']);

    // Check if a token already exists for this alert
    $stmt = $conn->prepare("SELECT token, used FROM alert_verification_tokens WHERE alert_id = ?");
    $stmt->bind_param("i", $alert_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Fetch existing token and its status
        $stmt->bind_result($existing_token, $used);
        $stmt->fetch();
        $stmt->close();

        if ($used == 0) {
            // If token is not used, return it
            echo json_encode(["success" => true, "token" => $existing_token]);
            exit;
        } else {
            // If token is used, delete it and generate a new one
            $stmt = $conn->prepare("DELETE FROM alert_verification_tokens WHERE alert_id = ?");
            $stmt->bind_param("i", $alert_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Generate new token since the old one was used or doesn't exist
    $token = bin2hex(random_bytes(4));
    $expires_at = date('Y-m-d H:i:s', strtotime('+20 hours'));

    $stmt = $conn->prepare("INSERT INTO alert_verification_tokens (alert_id, token, expires_at, used) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("iss", $alert_id, $token, $expires_at);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "token" => $token]);
    } else {
        echo json_encode(["success" => false]);
    }
    $stmt->close();
}
?>
