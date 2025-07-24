<?php
header("Content-Type: application/json");
require("../conn.php");

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data["instanceId"])) {
    die(json_encode(["status" => "error", "message" => "Missing instanceId"]));
}

$instanceId = $conn->real_escape_string($data["instanceId"]);

// Insert into database
$sql = "INSERT INTO ems (instance_id) VALUES ('$instanceId')";
if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "Instance ID saved"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save"]);
}

$conn->close();
?>
